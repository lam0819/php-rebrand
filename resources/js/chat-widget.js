/*
 | The "Ask AI" assistant. Retrieval happens in the browser against the shared
 | InlaySQL WASM index; the retrieved pages plus the question are handed to a
 | Livewire component, which calls the model server-side (the API key can never
 | be exposed to the client) and renders the answer with links to those pages.
 |
 | The conversation is persisted in localStorage and the panel size is a
 | browser preference; both stay on the device and can be cleared.
 */

import { hybridSearch } from './search-index.js';

const RETRIEVAL_LIMIT = 6;
const HISTORY_KEY = 'php-rebrand:assistant-history';
const SIZE_KEY = 'php-rebrand:assistant-size';
const MAX_MESSAGES = 20;
const SIZES = ['sm', 'md', 'lg'];

function messagesEl() {
  return document.querySelector('[data-assistant-messages]');
}

function scrollToBottom() {
  const el = messagesEl();
  if (el) el.scrollTop = el.scrollHeight;
}

function readDomHistory() {
  const el = document.querySelector('[data-assistant][data-ai-history]');
  if (!el) return null;

  try {
    // dataset decodes the HTML entities Blade escaped into the attribute.
    return JSON.parse(el.dataset.aiHistory || '[]');
  } catch {
    return null;
  }
}

function persistHistory() {
  const history = readDomHistory();
  if (!Array.isArray(history)) return;

  try {
    if (history.length === 0) {
      localStorage.removeItem(HISTORY_KEY);
    } else {
      localStorage.setItem(HISTORY_KEY, JSON.stringify(history.slice(-MAX_MESSAGES)));
    }
  } catch {
    // Storage unavailable (private mode, quota) — the chat still works.
  }
}

function restoreHistory() {
  let saved = null;

  try {
    saved = JSON.parse(localStorage.getItem(HISTORY_KEY) || 'null');
  } catch {
    saved = null;
  }

  if (Array.isArray(saved) && saved.length > 0) {
    window.Livewire?.dispatch('restore-ai', { messages: saved });
  }
}

function applySize() {
  const panel = document.querySelector('[data-assistant-panel]');
  if (!panel) return;

  let size = 'md';

  try {
    size = localStorage.getItem(SIZE_KEY) || 'md';
  } catch {
    size = 'md';
  }

  if (!SIZES.includes(size)) size = 'md';

  panel.classList.remove('is-sm', 'is-md', 'is-lg');
  panel.classList.add(`is-${size}`);

  document.querySelectorAll('[data-assistant-size]').forEach((button) => {
    button.classList.toggle('active', button.dataset.assistantSize === size);
  });
}

// Show the visitor's question immediately; Livewire replaces the list with the
// server state (which includes the same question) when the answer is ready.
function showOptimisticQuestion(question) {
  const el = messagesEl();
  if (!el) return;

  el.querySelector('.assistant-empty')?.remove();

  const row = document.createElement('div');
  row.className = 'assistant-msg assistant-msg-user';

  const bubble = document.createElement('div');
  bubble.className = 'assistant-bubble';
  bubble.textContent = question;

  row.appendChild(bubble);
  el.appendChild(row);
  scrollToBottom();
}

document.addEventListener('click', (event) => {
  const button = event.target.closest?.('[data-assistant-size]');
  if (!button) return;

  const size = button.dataset.assistantSize;

  if (SIZES.includes(size)) {
    try {
      localStorage.setItem(SIZE_KEY, size);
    } catch {
      // Ignore storage errors.
    }

    applySize();
  }
});

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

let restoreTried = false;

document.addEventListener('livewire:init', () => {
  window.Livewire.hook('morph.updated', () => {
    // Restore once, before the first persist would wipe the saved history.
    if (!restoreTried) {
      restoreTried = true;
      restoreHistory();
    }

    applySize();
    scrollToBottom();
    persistHistory();
  });
});

document.addEventListener('livewire:navigated', () => {
  restoreTried = false;
});
