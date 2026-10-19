# EcoWin: daftar periksa rilis & produksi

Panduan singkat untuk memindahkan EcoWin dari mesin lokal ke server sungguhan. Semua langkah berurutan.

## 0. Syarat dasar

- **HTTPS wajib** untuk semua yang tampil ke pengguna (API, panel, web nasabah). Tanpa HTTPS:
  kamera pemindai QR **tidak berfungsi** (browser memblokirnya di luar HTTPS/localhost), cookie sesi aman tidak terkirim, dan login Google ditolak.
- PHP 8.3 dengan ekstensi: `mbstring`, `intl`, `pdo_mysql`, `gd`, `bcmath`, `zip`, `exif`, `curl`, `openssl`.
- MySQL 8 (SQLite hanya untuk pengembangan/tes).
- Penjadwal: tambahkan cron `* * * * * php /path/backend/artisan schedule:run` (membersihkan notifikasi > 90 hari tiap malam dan mengirim pengumuman terjadwal tiap menit).

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
php artisan optimize           # cache config + route + event + view
php artisan filament:optimize  # cache komponen & ikon Filament (mempercepat panel)
php artisan storage:link
```

> Tanpa `optimize`/`filament:optimize`, halaman panel pertama bisa sangat lambat karena tampilan dikompilasi saat diminta
> (di mesin pengembangan Windows terukur sampai 2 menit pada permintaan pertama). Jalankan ulang kedua perintah setiap rilis.

### Migrasi yang ditambahkan pada rilis terbaru
Semua dijalankan otomatis oleh `php artisan migrate --force`:
- `add_verifikasi_to_titik_biopori`: nasabah dapat mengajukan titik Biopori; titik lama otomatis berstatus disetujui.
- `add_soft_deletes_to_users_and_nasabah`: dasar fitur "Hapus data" akun tidak aktif.
- `create_notifikasi_table`: notifikasi dalam aplikasi untuk nasabah.

### Fitur yang perlu diketahui operator
- **Hapus data akun** (Admin saja, hanya akun tidak aktif): data pribadi dihapus permanen dan tidak bisa dipulihkan; riwayat transaksi dan audit tetap ada secara anonim. Syarat nasabah: saldo Rp 0 dan tidak ada penarikan/aktivitas yang menunggu. **Cadangkan database sebelum menghapus** bila ragu.
- **Cetak & ekspor laporan** (Menu Laporan): Admin boleh memilih Bank Sampah atau semua; Petugas dikunci ke Bank Sampah miliknya. Setiap cetak/ekspor tercatat di audit log.
- **Scan QR**: pustaka pembaca (`public/vendor/jsQR.js`, jsQR 1.4.0, Apache-2.0) disajikan dari server sendiri; tidak ada CDN.

Pemeriksaan:
- `DemoDataSeeder` menolak berjalan di production. Jangan menjalankan `db:seed` penuh di produksi kecuali untuk data awal (bank sampah, kategori, harga).
- Folder `storage/` dan `bootstrap/cache/` hanya boleh ditulis oleh user server web.
- Berkas bukti (foto biopori) disimpan di disk **privat** dan disajikan lewat rute yang diotorisasi; jangan diarahkan langsung oleh web server.
- Cadangkan database harian dan uji pemulihan (lihat bagian 6).
- Peta memakai tile OpenStreetMap. Server tile gratis OSM tidak ditujukan untuk lalu lintas besar: untuk produksi pakai penyedia tile (mis. MapTiler/Stadia, punya paket gratis) dan ganti URL tile di `web/src/components/PetaBiopori.jsx`, `web/src/components/FormTitik.jsx`, `backend/resources/views/filament/pages/peta-biopori.blade.php`, `backend/resources/views/filament/forms/ambil-lokasi.blade.php`, dan `PetaBioporiCard.kt`/`AjukanTitikForm.kt` (Android, `TileSourceFactory.MAPNIK`).

## 2. Google Cloud

Pada client **Web application**:
- Authorized JavaScript origins: `https://api.domain-anda.id`, `https://app.domain-anda.id`
- Authorized redirect URI: `https://api.domain-anda.id/auth/google/callback`

Client **Android**: package `id.ecowin.app` + **SHA-1 kunci rilis** (bukan kunci debug). Ambil dengan:

```bash
keytool -list -v -keystore ecowin-release.jks -alias ecowin
```

Bila aplikasi diunggah ke Play Store dengan Play App Signing, tambahkan juga SHA-1 dari Play Console (App signing).

Ubah status OAuth consent screen dari *Testing* ke *In production* agar akun selain Test user dapat masuk (ikuti proses verifikasi Google bila diminta). Hapus client OAuth lama yang sudah tidak dipakai.

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

- Build `release` **menolak** alamat selain HTTPS dan sudah memakai R8 (aturan di `app/proguard-rules.pro`, termasuk osmdroid).
- Aplikasi meminta izin **lokasi** hanya saat nasabah mengetuk "Gunakan lokasi saya" untuk mengajukan titik Biopori. Cantumkan ini di deskripsi/kebijakan privasi Play Store.
- `releaseCheck` hanya untuk menguji hasil R8 di perangkat sendiri (kunci debug, HTTP lokal diizinkan). Jangan dibagikan.
- Simpan keystore rilis di tempat aman (di luar repo) dan cadangkan; kehilangan keystore berarti tidak bisa memperbarui aplikasi.

## 5. Tes otomatis (CI)

`.github/workflows/ci.yml` menjalankan tes backend (`php artisan test`), web (`npm test` + build), dan Android (`testDebugUnitTest` + `assembleDebug`) pada setiap push ke `main` dan setiap pull request. Aktifkan "Require status checks" pada branch `main` di GitHub agar penggabungan hanya bisa dilakukan bila CI hijau.

## 6. Pencadangan

- Cadangan database harian (mis. `mysqldump --single-transaction`), simpan di luar server yang sama, dan **uji pemulihan** minimal sekali per kuartal.
- Cadangkan folder `storage/app` (foto bukti dan identitas di disk privat).
- Kunci rilis Android (keystore) dan `.env` produksi disimpan di pengelola rahasia, bukan di repo.

## 7. Sebelum go-live

- [ ] Putar ulang semua secret yang pernah terlihat (Google Client Secret, token apa pun).
- [ ] `php artisan test` (backend), `npm test` (web), `./gradlew :app:testDebugUnitTest` (Android) hijau, dan CI di GitHub hijau.
- [ ] Uji alur: daftar nasabah baru → verifikasi petugas → setoran → penarikan → persetujuan → notifikasi muncul.
- [ ] Uji alur titik Biopori: nasabah mengajukan → petugas menyetujui → nasabah melapor aktivitas.
- [ ] Uji scan QR di HP sungguhan lewat HTTPS (kamera, fokus, senter) dengan kartu nasabah yang dicetak.
- [ ] Uji login Admin, Petugas, dan Nasabah di domain produksi (HTTPS).
- [ ] Uji cetak dan ekspor laporan sebagai Admin (semua bank) dan Petugas (hanya bank sendiri).
- [ ] Periksa `storage/logs` tidak berisi token atau data pribadi.
- [ ] Server tile peta sudah diganti ke penyedia yang sesuai (bagian 1).
