# EcoWin Web (Nasabah)

React + Vite + Tailwind. Memakai REST API Laravel yang sama dengan aplikasi Android (satu sumber kebenaran: Laravel + MySQL).
Admin dan Petugas memakai dashboard Filament.

## Menjalankan

```bash
cd web
npm install
cp .env.example .env     # VITE_API_BASE_URL (alamat API Laravel)
npm run dev              # http://localhost:5173
npm test                 # vitest
npm run build            # hasil di dist/
```

Backend harus berjalan (`php artisan serve`). Di `backend/.env`: `ECOWIN_WEB_URL` dan `ECOWIN_WEB_ORIGINS` memuat alamat web ini.

## Satu halaman login untuk semua role
Tidak ada halaman login di aplikasi ini. Login & pendaftaran adalah **satu halaman Laravel** (`/admin/login`, alias `/masuk`),
sama untuk Admin, Petugas, dan Nasabah. Tidak perlu "Authorized JavaScript origins" di Google; cukup redirect URI
`http://localhost:8000/auth/google/callback`.

Alur Nasabah (Authorization Code + PKCE):
1. Web membuat `code_verifier` (disimpan di sessionStorage) dan mengalihkan ke `/masuk?app=web&c=<code_challenge>`.
2. Pengguna login/daftar dengan Google di halaman Laravel; server memverifikasi token Google dan role dari database.
3. Laravel mengalihkan ke `/auth/callback?code=<kode sekali pakai, 60 detik>`.
4. Web menukar `code` + `verifier` di `POST /api/auth/exchange` menjadi access + refresh token. Kode yang bocor tidak berguna tanpa verifier.

Admin/Petugas: login yang sama, lalu masuk ke dashboard Filament (sesi web Laravel persisten).

## Pendaftaran akun baru
`/daftar` → Google → lengkapi nama, no. HP, alamat, pilih Bank Sampah. Email dan Google ID diambil dari sesi server (hasil verifikasi
Google), bukan dari form. Role selalu nasabah; status verifikasi `pending` sampai petugas menyetujui. Selama pending, penarikan saldo dikunci.

## Sesi
Access token pendek + refresh token yang dirotasi, disimpan di `localStorage` (`ecowin.session`) dan dikirim lewat header
`Authorization: Bearer` (bukan cookie, jadi tidak rentan CSRF). Token kedaluwarsa di-refresh otomatis; sesi dicabut/logout menghapus token lokal.

## Struktur
`src/api` klien + endpoint · `src/context` auth · `src/components` komponen reusable · `src/layouts` · `src/pages` · `src/utils`

## Peta lokasi Biopori & BioporiPrint
Halaman Biopori menampilkan peta (Leaflet + OpenStreetMap, tanpa API key) berisi titik Biopori milik Bank Sampah nasabah.
Titik BioporiPrint ditandai khusus dan bisa difilter; "Lapor di sini" membuka form lapor dengan lokasi terpilih.
Titik tanpa koordinat tidak digambar dan disebutkan di bawah peta. Peta dimuat terpisah (lazy) agar halaman lain tetap ringan.
