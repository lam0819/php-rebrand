<?php

declare(strict_types=1);

use App\Ai\ManualAnswerAgent;
use App\Docs\DTO\DocPageDTO;
use App\Docs\DTO\MetadataDTO;
use App\Docs\Enums\DocumentType;
use App\Docs\Persistence\EloquentDocsPageRepository;
use App\Docs\Search\DocSearch;
use Livewire\Livewire;

beforeEach(function () {
    config([
        'assistant.enabled' => true,
        'assistant.provider' => 'openrouter',
        'assistant.models' => ['test/model:free'],
    ]);
});

function assistantContext(): array
{
    return [[
        'slug' => 'reference-strings-functions-str-replace',
        'title' => 'str_replace',
        'kind' => 'refentry',
        'purpose' => 'Replace all occurrences of a search string',
        'excerpt' => 'str_replace replaces every occurrence of the search value with the replacement.',
    ]];
}

it('answers from the retrieved pages, links the cited source and names the model', function () {
    ManualAnswerAgent::fake(['Use **str_replace** [1] to replace text.']);

    Livewire::test('ask')
        ->set('open', true)
        ->call('ask', 'How do I replace part of a string?', assistantContext())
        ->assertSet('error', null)
        ->assertSet('messages.0.role', 'user')
        ->assertSet('messages.1.role', 'assistant')
        ->assertSet('messages.1.model', 'test/model:free')
        ->assertSee('str_replace')
        ->assertSee('via test/model:free')
        ->assertSee('/manual/reference-strings-functions-str-replace', escape: false);
});

it('strips raw html from the model answer', function () {
    ManualAnswerAgent::fake(['Hello <script>alert(1)</script> there [1]']);

    Livewire::test('ask')
        ->set('open', true)
        ->call('ask', 'How do I replace a string?', assistantContext())
        ->assertDontSee('<script>', escape: false);
});

it('falls back to server-side retrieval when the browser sends no context', function () {
    (new EloquentDocsPageRepository)->save(new DocPageDTO(
        docId: 'reference-strings-functions-str-replace',
        slug: 'reference-strings-functions-str-replace',
        title: 'str_replace',
        type: DocumentType::RefEntry,
        sourcePath: 'reference/strings/functions/str-replace.xml',
        metadata: new MetadataDTO(purpose: 'Replace all occurrences of a search string'),
        content: 'str_replace replaces every occurrence of the search value.',
    ));
    app(DocSearch::class)->rebuild();

    ManualAnswerAgent::fake(['Use str_replace [1].']);

    Livewire::test('ask')
        ->set('open', true)
        ->call('ask', 'str_replace', [])
        ->assertSet('error', null)
        ->assertSee('str_replace');
});

it('errors when nothing can be found', function () {
    ManualAnswerAgent::fake();

    Livewire::test('ask')
        ->set('open', true)
        ->call('ask', 'a question with no matching pages', [])
        ->assertSet('error', fn ($error) => is_string($error) && $error !== '');
});

it('is unavailable when no api key is configured', function () {
    config(['assistant.enabled' => false]);

    Livewire::test('ask')
        ->set('open', true)
        ->call('ask', 'How do I replace a string?', assistantContext())
        ->assertSet('error', 'The assistant is not available right now.');
});

it('rejects an over-long question', function () {
    ManualAnswerAgent::fake();

    Livewire::test('ask')
        ->set('open', true)
        ->call('ask', str_repeat('a', (int) config('assistant.max_question_chars') + 1), assistantContext())
        ->assertSet('error', fn ($error) => is_string($error) && str_contains($error, 'under'));
});
