import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

/**
 * Content-Security-Policy untuk build produksi, disisipkan sebagai <meta> sehingga berlaku di hosting statis mana pun.
 * Token login disimpan di localStorage, jadi CSP adalah pertahanan utama bila suatu saat ada celah XSS: hanya skrip dari
 * origin sendiri yang boleh jalan dan permintaan jaringan hanya boleh ke server API. Sumber eksternal yang sah hanya tiga:
 * server API (data, foto profil), foto akun Google, dan ubin peta OpenStreetMap.
 * Catatan: <meta> tidak mendukung frame-ancestors; perlindungan clickjacking dipasang lewat header di server web (lihat DEPLOY.md).
 */
function kebijakanCsp(apiBase) {
  let apiOrigin;
  try { apiOrigin = new URL(apiBase).origin; } catch { apiOrigin = 'http://localhost:8000'; }
  return [
    "default-src 'self'",
    "script-src 'self'",
    "style-src 'self' 'unsafe-inline'", // atribut style dari React/Leaflet; skrip tetap ketat
    `img-src 'self' data: blob: ${apiOrigin} https://*.googleusercontent.com https://tile.openstreetmap.org`,
    "font-src 'self'",
    `connect-src 'self' ${apiOrigin}`,
    "object-src 'none'",
    "base-uri 'self'",
    "form-action 'self'",
  ].join('; ');
}

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), 'VITE_');
  const csp = kebijakanCsp(env.VITE_API_BASE_URL || 'http://localhost:8000/api');

  return {
    plugins: [
      react(),
      tailwindcss(),
      {
        name: 'ecowin-csp',
        apply: 'build',
        transformIndexHtml: (html) => html.replace('<meta charset="UTF-8" />', `<meta charset="UTF-8" />\n    <meta http-equiv="Content-Security-Policy" content="${csp}" />`),
      },
    ],
    server: { port: 5173, strictPort: true },
    test: {
      environment: 'jsdom',
      globals: true,
      setupFiles: './src/test-setup.js',
    },
  };
});
