import '@testing-library/jest-dom/vitest';

// Node baru (>=22) menyediakan `localStorage` eksperimental yang bisa menimpa milik jsdom.
// Pakai penyimpanan memori sederhana agar tes konsisten di semua versi Node.
if (typeof globalThis.localStorage?.clear !== 'function') {
  const store = new Map();
  const memory = {
    getItem: (k) => (store.has(k) ? store.get(k) : null),
    setItem: (k, v) => { store.set(k, String(v)); },
    removeItem: (k) => { store.delete(k); },
    clear: () => { store.clear(); },
  };
  Object.defineProperty(globalThis, 'localStorage', { value: memory, configurable: true });
  if (typeof window !== 'undefined') Object.defineProperty(window, 'localStorage', { value: memory, configurable: true });
}
