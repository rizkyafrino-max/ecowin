# EcoWin: daftar periksa rilis & produksi

Panduan singkat untuk memindahkan EcoWin dari mesin lokal ke server sungguhan. Semua langkah berurutan.

## 1. Backend (Laravel)

`backend/.env` di server, nilai wajib:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.domain-anda.id

SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
SESSION_DOMAIN=null            # atau domain induk bila web dan API satu domain

DB_CONNECTION=mysql            # sqlite hanya untuk pengembangan
LOG_LEVEL=warning

GOOGLE_CLIENT_ID=              # client "Web application"
GOOGLE_CLIENT_SECRET=          # secret yang sudah diputar; jangan pernah di-commit
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
GOOGLE_EXTRA_CLIENT_IDS=       # opsional: client Android/iOS tambahan (audience yang diterima)

ECOWIN_WEB_ORIGINS=https://app.domain-anda.id
ECOWIN_WEB_URL=https://app.domain-anda.id
```

Perintah rilis:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan storage:link
```

Pemeriksaan:
- `DemoDataSeeder` menolak berjalan di production. Jangan menjalankan `db:seed` penuh di produksi kecuali untuk data awal (bank sampah, kategori, harga).
- Folder `storage/` dan `bootstrap/cache/` hanya boleh ditulis oleh user server web.
- Berkas bukti (foto biopori) disimpan di disk **privat** dan disajikan lewat rute yang diotorisasi; jangan diarahkan langsung oleh web server.
- Cadangkan database harian dan uji pemulihan.

## 2. Google Cloud

Pada client **Web application**:
- Authorized JavaScript origins: `https://api.domain-anda.id`, `https://app.domain-anda.id`
- Authorized redirect URI: `https://api.domain-anda.id/auth/google/callback`

Client **Android**: package `id.ecowin.app` + **SHA-1 kunci rilis** (bukan kunci debug). Ambil dengan:

```bash
keytool -list -v -keystore ecowin-release.jks -alias ecowin
```

Bila aplikasi diunggah ke Play Store dengan Play App Signing, tambahkan juga SHA-1 dari Play Console (App signing).

Ubah status OAuth consent screen dari *Testing* ke *In production* agar akun selain Test user dapat masuk (ikuti proses verifikasi Google bila diminta).

## 3. Web Nasabah (React)

```bash
cd web
echo "VITE_API_BASE_URL=https://api.domain-anda.id/api" > .env.production
npm ci && npm run build        # keluaran di web/dist
```

Sajikan `web/dist` lewat HTTPS dan arahkan semua rute yang tidak dikenal ke `index.html` (SPA).

## 4. Android

`android-app/gradle.properties` milik Anda (JANGAN di-commit):

```
ECOWIN_API_BASE_URL=https://api.domain-anda.id/api/
ECOWIN_GOOGLE_WEB_CLIENT_ID=<client ID web>
ECOWIN_KEYSTORE_FILE=C:/jalur/ke/ecowin-release.jks
ECOWIN_KEYSTORE_PASSWORD=...
ECOWIN_KEY_ALIAS=ecowin
ECOWIN_KEY_PASSWORD=...
```

Build:

```bash
cd android-app
./gradlew :app:bundleRelease     # AAB untuk Play Store
./gradlew :app:assembleRelease   # APK
```

- Build `release` **menolak** alamat selain HTTPS dan sudah memakai R8 (aturan di `app/proguard-rules.pro`).
- `releaseCheck` hanya untuk menguji hasil R8 di perangkat sendiri (kunci debug, HTTP lokal diizinkan). Jangan dibagikan.
- Simpan keystore rilis di tempat aman (di luar repo) dan cadangkan; kehilangan keystore berarti tidak bisa memperbarui aplikasi.

## 5. Sebelum go-live

- [ ] Putar ulang semua secret yang pernah terlihat (Google Client Secret, token apa pun).
- [ ] `php artisan test` (backend), `npm test` (web), `./gradlew :app:testDebugUnitTest` (Android) hijau.
- [ ] Uji alur: daftar nasabah baru → verifikasi petugas → setoran → penarikan → persetujuan.
- [ ] Uji login Admin, Petugas, dan Nasabah di domain produksi (HTTPS).
- [ ] Periksa `storage/logs` tidak berisi token atau data pribadi.
