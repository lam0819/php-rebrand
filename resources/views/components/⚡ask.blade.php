<?php

use App\Ai\ManualAssistant;
use App\Docs\Persistence\DocPage;
use App\Docs\Search\DocSearch;
use App\Docs\Support\DocPagePresenter;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public bool $open = false;

    /**
     * The conversation, oldest first. User turns carry `text`; assistant turns
     * carry the raw `text`, rendered `html`, the `model` that answered, and
     * `sources`.
     *
     * @var list<array<string, mixed>>
     */
    public array $messages = [];

    public ?string $error = null;

    /** History is capped so localStorage cannot grow without bound. */
    public const MAX_MESSAGES = 20;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function clear(): void
    {
        $this->messages = [];
        $this->error = null;
    }

    /**
     * A compact, storage-friendly view of the conversation for the browser.
     *
     * @return list<array<string, mixed>>
     */
    public function history(): array
    {
        return array_map(static function (array $message): array {
            return $message['role'] === 'user'
                ? ['role' => 'user', 'text' => (string) ($message['text'] ?? '')]
                : [
                    'role' => 'assistant',
                    'text' => (string) ($message['text'] ?? ''),
                    'model' => $message['model'] ?? null,
                    'sources' => $message['sources'] ?? [],
                ];
        }, $this->messages);
    }

    /**
     * Restore a conversation persisted in the browser. Only the raw fields are
     * trusted — HTML is rebuilt here from sanitized Markdown — and a live
     * conversation is never overwritten.
     *
     * @param  array<int, mixed>  $messages
     */
    #[On('restore-ai')]
    public function restore(array $messages): void
    {
        if ($this->messages !== []) {
            return;
        }

        $restored = [];

        foreach (array_slice($messages, -self::MAX_MESSAGES) as $message) {
            if (! is_array($message)) {
                continue;
            }

            $text = is_string($message['text'] ?? null) ? trim($message['text']) : '';

            if ($text === '') {
                continue;
            }

            if (($message['role'] ?? '') === 'user') {
                $restored[] = ['role' => 'user', 'text' => mb_substr($text, 0, 1000)];

                continue;
            }

            if (($message['role'] ?? '') !== 'assistant') {
                continue;
            }

            $sources = $this->normaliseSources(is_array($message['sources'] ?? null) ? $message['sources'] : []);

            $restored[] = [
                'role' => 'assistant',
                'text' => mb_substr($text, 0, 8000),
                'html' => $this->renderAnswer($text, $sources),
                'model' => is_string($message['model'] ?? null) ? mb_substr($message['model'], 0, 120) : null,
                'sources' => $sources,
            ];
        }

        $this->messages = $restored;
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
        $this->error = null;
        $question = trim($question);

        // The browser optimistically shows the question while this runs; keep a
        // single copy in state so a re-render cannot duplicate it.
        $alreadyAsked = ($this->messages[count($this->messages) - 1] ?? null)['text'] ?? null;

        if ($alreadyAsked !== $question) {
            $this->messages[] = ['role' => 'user', 'text' => $question];
        }

        if (! config('assistant.enabled')) {
            $this->error = 'The assistant is not available right now.';

            return;
        }

        $max = (int) config('assistant.max_question_chars');

        if (mb_strlen($question) < 3) {
            $this->error = 'Please ask a slightly longer question.';

            return;
        }

        if (mb_strlen($question) > $max) {
            $this->error = "Please keep questions under {$max} characters.";

            return;
        }

        if (! $this->withinRateLimit()) {
            $this->error = 'That is a lot of questions — please try again in a minute.';

            return;
        }

        $sources = $this->normaliseSources($context) ?: $this->retrieve($question);

        if ($sources === []) {
            $this->error = 'I could not find anything about that in the manual. Try a function name like str_replace.';

            return;
        }

        try {
            $result = app(ManualAssistant::class)->answer($question, $this->contextFor($sources));
        } catch (Throwable $exception) {
            report($exception);
            $this->error = 'The assistant is temporarily unavailable. Please try again.';

            return;
        }

        $this->messages[] = [
            'role' => 'assistant',
            'text' => $result['text'],
            'html' => $this->renderAnswer($result['text'], $sources),
            'model' => $result['model'],
            'sources' => $this->citedSources($sources, $result['text']),
        ];
    }

    /**
     * Convert the model's Markdown into safe HTML, turning [n] citations into
     * links to the pages we retrieved (never model-authored URLs).
     *
     * @param  list<array{slug: string, title: string, kind: string, purpose: string, excerpt: string}>  $sources
     */
    private function renderAnswer(string $text, array $sources): string
    {
        $linked = preg_replace_callback('/\[(\d+)\]/', static function (array $match) use ($sources): string {
            $index = (int) $match[1];

            if ($index < 1 || $index > count($sources)) {
                return $match[0];
            }

            return '['.$index.'](/manual/'.$sources[$index - 1]['slug'].')';
        }, $text);

        return Str::markdown($linked ?? $text, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
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
     * The pages the answer cited, or the top few when it cited none.
     *
     * @param  list<array{slug: string, title: string, kind: string, purpose: string, excerpt: string}>  $sources
     * @return list<array{slug: string, title: string, kind: string, purpose: string, excerpt: string}>
     */
    private function citedSources(array $sources, string $text): array
    {
        preg_match_all('/\[(\d+)\]/', $text, $matches);

        $cited = [];

        foreach ($matches[1] ?? [] as $index) {
            $index = (int) $index;

            if ($index >= 1 && $index <= count($sources)) {
                $cited[$index] = true;
            }
        }

        if ($cited === []) {
            return array_slice($sources, 0, 3);
        }

        $indices = array_keys($cited);
        sort($indices);

        return array_values(array_map(static fn (int $index): array => $sources[$index - 1], $indices));
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

<div class="assistant" data-assistant data-ai-history="{{ json_encode($this->history(), JSON_UNESCAPED_SLASHES) }}">
  <button
    type="button"
    class="assistant-fab"
    wire:click="toggle"
    aria-label="{{ $open ? 'Close the assistant' : 'Ask AI about the manual' }}"
    aria-expanded="{{ $open ? 'true' : 'false' }}"
  >
    @if ($open)
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
    @else
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.9 4.6L18.5 9.5 13.9 11.4 12 16l-1.9-4.6L5.5 9.5l4.6-1.9z"/><path d="M18 15l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8z"/></svg>
    @endif
    <span class="assistant-fab-label">Ask AI</span>
  </button>

    <div class="assistant-panel {{ $open ? 'open' : '' }}" data-assistant-panel aria-hidden="{{ $open ? 'false' : 'true' }}">
      <div class="assistant-head">
        <div class="assistant-head-title">
          <span class="assistant-dot" aria-hidden="true"></span>
          <strong>Ask the manual</strong>
        </div>
        <div class="assistant-tools">
          <div class="assistant-sizes" role="group" aria-label="Panel size">
            <button type="button" class="assistant-size" data-assistant-size="sm" aria-label="Small" title="Small">S</button>
            <button type="button" class="assistant-size" data-assistant-size="md" aria-label="Medium" title="Medium">M</button>
            <button type="button" class="assistant-size" data-assistant-size="lg" aria-label="Large" title="Large">L</button>
          </div>
          @if ($messages !== [])
            <button type="button" class="assistant-clear" wire:click="clear">Clear</button>
          @endif
        </div>
      </div>

      <div class="assistant-messages" data-assistant-messages>
        @forelse ($messages as $message)
          @if ($message['role'] === 'user')
            <div class="assistant-msg assistant-msg-user">
              <div class="assistant-bubble">{{ $message['text'] }}</div>
            </div>
          @else
            <div class="assistant-msg assistant-msg-bot">
              <div class="assistant-bubble">
                <div class="assistant-answer">{!! $message['html'] !!}</div>
                @if (! empty($message['sources']))
                  <div class="assistant-sources">
                    @foreach ($message['sources'] as $source)
                      <a class="assistant-source" href="/manual/{{ $source['slug'] }}" wire:navigate>
                        <span class="assistant-source-title">{{ $source['title'] }}</span>
                        @if ($source['purpose'])<span class="assistant-source-desc">{{ $source['purpose'] }}</span>@endif
                      </a>
                    @endforeach
                  </div>
                @endif
              </div>
              @if (! empty($message['model']))
                <span class="assistant-model">via {{ $message['model'] }}</span>
              @endif
            </div>
          @endif
        @empty
          <div class="assistant-empty">
            <p><strong>Ask anything about the PHP manual.</strong></p>
            <p class="assistant-hint">It searches the manual and answers with links to the pages it used. Answers come from a free AI model and may be imperfect.</p>
          </div>
        @endforelse

        <div class="assistant-msg assistant-msg-bot" wire:loading wire:target="ask">
          <div class="assistant-bubble assistant-typing" aria-label="Thinking">
            <span></span><span></span><span></span>
          </div>
        </div>

        @if ($error)
          <p class="assistant-error">{{ $error }}</p>
        @endif
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
        <button type="submit" class="assistant-send" aria-label="Send" wire:loading.attr="disabled" wire:target="ask">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
        </button>
      </form>
    </div>
</div>
