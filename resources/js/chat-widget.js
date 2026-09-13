/*
 | The "Ask AI" assistant. Retrieval happens in the browser against the shared
 | InlaySQL WASM index; the retrieved pages plus the question are handed to a
 | Livewire component, which calls the model server-side (the API key can never
 | be exposed to the client) and renders the answer with links to those pages.
 */

import { hybridSearch } from './search-index.js';

const RETRIEVAL_LIMIT = 6;

document.addEventListener('submit', async (event) => {
  const form = event.target.closest?.('[data-assistant-form]');
  if (!form) return;

  event.preventDefault();

  const input = form.querySelector('[data-assistant-input]');
  const question = (input?.value || '').trim();

  if (question.length < 3) return;

  if (input) input.value = '';

  // Retrieve the most relevant manual pages first; dispatch immediately on
  // failure so the server can fall back to its own FTS retrieval.
  let context = [];

  try {
    const results = await hybridSearch(question, { limit: RETRIEVAL_LIMIT });
    context = results.map((r) => ({
      slug: r.slug,
      title: r.title,
      kind: r.type,
      purpose: r.purpose,
      excerpt: r.body,
    }));
  } catch {
    // Server-side retrieval is the fallback.
  }

  window.Livewire?.dispatch('ask-ai', { question, context });
});
