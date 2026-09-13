/*
 | Cookie / storage consent. The site uses only essential cookies (the Laravel
 | session and CSRF cookie) plus local storage for theme, panel size, the
 | assistant conversation and this choice — no tracking or advertising. The
 | banner records the visitor's decision and never sets a non-essential cookie
 | before it is made.
 */

const CONSENT_KEY = 'php-rebrand:cookie-consent';

function decision() {
  try {
    return localStorage.getItem(CONSENT_KEY);
  } catch {
    return 'unavailable';
  }
}

function applyConsent() {
  const banner = document.querySelector('[data-cookie-consent]');
  if (!banner) return;

  if (decision()) {
    banner.classList.remove('show');
    banner.hidden = true;
    return;
  }

  banner.hidden = false;
  requestAnimationFrame(() => banner.classList.add('show'));
}

document.addEventListener('click', (event) => {
  const choice = event.target.closest?.('[data-cookie-choice]');
  if (!choice) return;

  try {
    localStorage.setItem(CONSENT_KEY, choice.dataset.cookieChoice);
  } catch {
    // Storage unavailable — hide the banner for this page view regardless.
  }

  const banner = choice.closest('[data-cookie-consent]');
  if (!banner) return;

  banner.classList.remove('show');
  window.setTimeout(() => {
    banner.hidden = true;
  }, 200);
});

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', applyConsent);
} else {
  applyConsent();
}

document.addEventListener('livewire:navigated', applyConsent);
