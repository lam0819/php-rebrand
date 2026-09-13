/*
 | The "Ask AI" assistant. Retrieval happens in the browser against the shared
 | InlaySQL WASM index; the retrieved pages plus the question are handed to a
 | Livewire component, which calls the model server-side (the API key can never
 | be exposed to the client) and renders the answer with links to those pages.
 */

import { hybridSearch } from './search-index.js';

const RETRIEVAL_LIMIT = 6;

function messagesEl() {
  return document.querySelector('[data-assistant-messages]');
}

function scrollToBottom() {
  const el = messagesEl();
  if (el) el.scrollTop = el.scrollHeight;
}

// Show the visitor's question immediately; Livewire replaces the list with the
// server state (which includes the same question) when the answer is ready.
function showOptimisticQuestion(question) {
  const el = messagesEl();
  if (!el) return;

  const empty = el.querySelector('.assistant-empty');
  if (empty) empty.remove();

  const row = document.createElement('div');
  row.className = 'assistant-msg assistant-msg-user';

  const bubble = document.createElement('div');
  bubble.className = 'assistant-bubble';
  bubble.textContent = question;

  row.appendChild(bubble);
  el.appendChild(row);
  scrollToBottom();
}

document.addEventListener('submit', async (event) => {
  const form = event.target.closest?.('[data-assistant-form]');
  if (!form) return;

  event.preventDefault();

  const input = form.querySelector('[data-assistant-input]');
  const question = (input?.value || '').trim();

  if (question.length < 3) return;

  if (input) input.value = '';
  showOptimisticQuestion(question);

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

// Keep the conversation pinned to the newest message after each render.
document.addEventListener('livewire:init', () => {
  window.Livewire.hook('morph.updated', () => scrollToBottom());
});
