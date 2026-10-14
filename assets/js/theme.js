(() => {
  'use strict';
  const storageKey = 'cmb-theme';
  const root = document.documentElement;
  const button = document.querySelector('[data-theme-toggle]');
  const label = document.querySelector('[data-theme-label]');
  const saved = localStorage.getItem(storageKey);
  const systemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  function applyTheme(theme) {
    const dark = theme === 'dark';
    root.dataset.theme = dark ? 'dark' : 'light';
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', dark ? '#201e1a' : '#fbfaf7');
    if (button) {
      button.setAttribute('aria-pressed', String(dark));
      button.setAttribute('aria-label', `Switch to ${dark ? 'light' : 'dark'} theme`);
    }
    if (label) label.textContent = dark ? 'Light' : 'Dark';
  }
  applyTheme(saved || (systemDark ? 'dark' : 'light'));
  button?.addEventListener('click', () => {
    const next = root.dataset.theme === 'dark' ? 'light' : 'dark';
    localStorage.setItem(storageKey, next);
    applyTheme(next);
  });
})();
