<?php

use App\Ai\ManualAnswerAgent;
use App\Docs\Persistence\DocPage;
use App\Docs\Search\DocSearch;
use App\Docs\Support\DocPagePresenter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public bool $open = false;

    public string $question = '';

    public ?string $answer = null;

    /** @var list<array{slug: string, title: string, kind: string, purpose: string, excerpt: string}> */
    public array $sources = [];

    public ?string $error = null;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    /**
     * Answer a question from the pages the browser retrieved from the InlaySQL
     * index. Falls back to server-side retrieval when the client sends none.
     *
     * @param  array<int, mixed>  $context
     */
    #[On('ask-ai')]
    public function ask(string $question, array $context = []): void
    {
        $this->answer = null;
        $this->error = null;
        $this->sources = [];
        $this->question = trim($question);

        if (! config('assistant.enabled')) {
            $this->error = 'The assistant is not available right now.';

            return;
        }

        $max = (int) config('assistant.max_question_chars');

        if (mb_strlen($this->question) < 3) {
            $this->error = 'Please ask a slightly longer question.';

            return;
        }

        if (mb_strlen($this->question) > $max) {
            $this->error = "Please keep questions under {$max} characters.";

            return;
        }

        if (! $this->withinRateLimit()) {
            $this->error = 'That is a lot of questions — please try again in a minute.';

            return;
        }

        $sources = $this->normaliseSources($context) ?: $this->retrieve($this->question);

        if ($sources === []) {
            $this->error = 'I could not find anything about that in the manual. Try a function name like str_replace.';

            return;
        }

        try {
            $response = (new ManualAnswerAgent($this->contextFor($sources)))
                ->prompt(
                    $this->question,
                    provider: config('assistant.provider'),
                    model: config('assistant.model'),
                    timeout: 60,
                );
        } catch (Throwable $exception) {
            report($exception);
            $this->error = 'The assistant is temporarily unavailable. Please try again.';

            return;
        }

        $data = $response instanceof Illuminate\Contracts\Support\Arrayable
            ? $response->toArray()
            : ['answer' => (string) $response, 'citations' => []];

        $text = is_string($data['answer'] ?? null) ? trim($data['answer']) : '';

        if ($text === '') {
            $this->error = 'The assistant did not return an answer. Please try again.';

            return;
        }

        $this->answer = Str::markdown($text, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $this->sources = $this->citedSources($sources, $this->citations($data['citations'] ?? [], count($sources)));
    }

    /**
     * @param  array<int, mixed>  $context
     * @return list<array{slug: string, title: string, kind: string, purpose: string, excerpt: string}>
     */
    private function normaliseSources(array $context): array
    {
        $limit = (int) config('assistant.sources');
        $chars = (int) config('assistant.excerpt_chars');
        $sources = [];

        foreach (array_slice($context, 0, $limit) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $slug = is_string($item['slug'] ?? null) ? $item['slug'] : '';
            $title = is_string($item['title'] ?? null) ? trim($item['title']) : '';

            if ($slug === '' || $title === '' || preg_match('/^[a-z0-9-]+$/', $slug) !== 1) {
                continue;
            }

            $sources[] = [
                'slug' => $slug,
                'title' => mb_substr($title, 0, 200),
                'kind' => is_string($item['kind'] ?? null) ? mb_substr($item['kind'], 0, 40) : 'page',
                'purpose' => is_string($item['purpose'] ?? null) ? mb_substr(trim($item['purpose']), 0, 300) : '',
                'excerpt' => is_string($item['excerpt'] ?? null) ? mb_substr(trim($item['excerpt']), 0, $chars) : '',
            ];
        }

        return $sources;
    }

    /**
     * Server-side fallback retrieval (FTS5/LIKE) when the browser sends none.
     *
     * @return list<array{slug: string, title: string, kind: string, purpose: string, excerpt: string}>
     */
    private function retrieve(string $question): array
    {
        $limit = (int) config('assistant.sources');
        $presenter = app(DocPagePresenter::class);

        $summaries = app(DocSearch::class)->summaries($question, $limit);

        if ($summaries === null) {
            $like = '%'.$question.'%';
            $summaries = DocPage::query()
                ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('slug', 'like', $like))
                ->orderBy('title')
                ->limit($limit)
                ->get()
                ->map(fn (DocPage $page) => $presenter->summary($page))
                ->all();
        }

        if ($summaries === []) {
            return [];
        }

        $contents = DocPage::query()->whereIn('slug', array_column($summaries, 'slug'))->pluck('content', 'slug');
        $chars = (int) config('assistant.excerpt_chars');

        return array_map(static function (array $summary) use ($contents, $chars): array {
            $text = trim((string) preg_replace('/\s+/u', ' ', (string) ($contents[$summary['slug']] ?? '')));

            return [
                'slug' => $summary['slug'],
                'title' => $summary['name'],
                'kind' => $summary['kind'],
                'purpose' => $summary['desc'],
                'excerpt' => mb_substr($text, 0, $chars),
            ];
        }, $summaries);
    }

    /**
     * @param  list<array{slug: string, title: string, kind: string, purpose: string, excerpt: string}>  $sources
     */
    private function contextFor(array $sources): string
    {
        $lines = [];

        foreach ($sources as $index => $source) {
            $number = $index + 1;
            $lines[] = "[{$number}] {$source['title']} ({$source['kind']})\n".trim($source['purpose']."\n".$source['excerpt']);
        }

        return implode("\n\n", $lines);
    }

    /**
     * @return list<int>
     */
    private function citations(mixed $raw, int $count): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $valid = [];

        foreach ($raw as $index) {
            $index = (int) $index;

            if ($index >= 1 && $index <= $count) {
                $valid[$index] = true;
            }
        }

        return array_keys($valid);
    }

    /**
     * @param  list<array{slug: string, title: string, kind: string, purpose: string, excerpt: string}>  $sources
     * @param  list<int>  $citations
     * @return list<array{slug: string, title: string, kind: string, purpose: string, excerpt: string}>
     */
    private function citedSources(array $sources, array $citations): array
    {
        return array_values(array_map(static fn (int $index): array => $sources[$index - 1], $citations));
    }

    private function withinRateLimit(): bool
    {
        $ip = (string) (request()->ip() ?: 'unknown');
        $minuteKey = 'assistant:minute:'.$ip;
        $dayKey = 'assistant:day:'.$ip;

        if (RateLimiter::tooManyAttempts($minuteKey, (int) config('assistant.throttle.per_minute'))) {
            return false;
        }

        if (RateLimiter::tooManyAttempts($dayKey, (int) config('assistant.throttle.per_day'))) {
            return false;
        }

        RateLimiter::hit($minuteKey, 60);
        RateLimiter::hit($dayKey, 86400);

        return true;
    }
}; ?>

<div class="assistant" data-assistant>
  <button
    type="button"
    class="assistant-fab"
    wire:click="toggle"
    aria-label="Ask AI about the manual"
    aria-expanded="{{ $open ? 'true' : 'false' }}"
  >
    @if ($open)
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
    @else
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.9 4.6L18.5 9.5 13.9 11.4 12 16l-1.9-4.6L5.5 9.5l4.6-1.9z"/><path d="M18 15l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8z"/></svg>
    @endif
    <span class="assistant-fab-label">Ask AI</span>
  </button>

  @if ($open)
    <div class="assistant-panel" data-assistant-panel>
      <div class="assistant-head">
        <strong>Ask the manual</strong>
        <span class="assistant-hint">Answers from a free AI model — may be imperfect. Always check the linked pages.</span>
      </div>

      <form class="assistant-form" data-assistant-form>
        <input
          type="text"
          data-assistant-input
          placeholder="How do I replace part of a string?"
          maxlength="{{ (int) config('assistant.max_question_chars') }}"
          autocomplete="off"
          aria-label="Your question"
        />
        <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="ask">Ask</button>
      </form>

      <div class="assistant-body">
        <div class="assistant-thinking" wire:loading wire:target="ask">Searching the manual and thinking…</div>

        @if ($error)
          <p class="assistant-error">{{ $error }}</p>
        @endif

        @if ($answer)
          <div class="assistant-answer">{!! $answer !!}</div>
        @endif

        @if ($sources)
          <div class="assistant-sources">
            @foreach ($sources as $source)
              <a class="assistant-source" href="/manual/{{ $source['slug'] }}" wire:navigate>
                <span class="assistant-source-title">{{ $source['title'] }}</span>
                @if ($source['purpose'])<span class="assistant-source-desc">{{ $source['purpose'] }}</span>@endif
              </a>
            @endforeach
          </div>
        @endif
      </div>
    </div>
  @endif
</div>
