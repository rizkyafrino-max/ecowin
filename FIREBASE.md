# Pemberitahuan instan (Firebase Cloud Messaging)

EcoWin memakai FCM hanya sebagai **"ping" pembangun**: pesan ke HP berisi `{"tipe":"kabar"}` tanpa judul, isi, atau nominal.
Begitu HP terbangun, aplikasi mengambil isi notifikasi dari API EcoWin yang terautentikasi, lalu menampilkannya (di layar kunci
hanya teks generik). Jadi data keuangan nasabah **tidak pernah lewat server Google**.

Tanpa langkah di bawah, semuanya tetap jalan: aplikasi memakai pengecekan berkala (±15 menit). Firebase membuat pemberitahuan
sampai dalam hitungan detik. Pengecekan berkala tetap aktif sebagai cadangan.

## 1. Buat proyek Firebase (gratis, sekali saja)
1. Buka <https://console.firebase.google.com> → **Add project** (Analytics boleh dimatikan; EcoWin tidak memakainya).
2. **Project settings → General → Your apps → Android**: package name `id.ecowin.app` (debug dan release sama).
   SHA-1 tidak diperlukan untuk FCM.
3. Unduh **`google-services.json`** dan letakkan di `android-app/app/google-services.json`.
   Berkas ini sudah di-ignore git. Setelah ada, build Android otomatis mengaktifkan Firebase.

## 2. Kunci akun layanan untuk server (rahasia!)
1. **Project settings → Service accounts → Generate new private key** → unduh berkas JSON.
2. Simpan di server, **di luar folder `public/`**, mis. `backend/storage/firebase/akun-layanan.json`
   (folder ini sudah di-ignore git). Batasi izin berkas (hanya pengguna web server yang bisa membaca).
3. Isi `.env` backend:
   ```
   FIREBASE_CREDENTIALS=storage/firebase/akun-layanan.json
   ```
   (jalur relatif terhadap folder `backend/`, atau jalur absolut), lalu `php artisan config:clear`.
4. Di Firebase Console pastikan **Cloud Messaging API (V1)** aktif.

> Jangan pernah meng-commit atau membagikan berkas akun layanan. Bila bocor: Service accounts → hapus kuncinya, buat yang baru.
> Server hanya mau mengirim tanda tangan ke `https://oauth2.googleapis.com/token`; berkas dengan alamat lain diabaikan.

## 3. Migrasi dan uji
```bash
php artisan migrate            # membuat tabel perangkat_push (token FCM terenkripsi)
```
Di HP: pasang build baru → Pengaturan → nyalakan **Pemberitahuan di HP** (token FCM baru dibuat saat itu, bukan saat aplikasi
dipasang). Tutup aplikasi, kirim pengumuman dari panel admin: notifikasi harus muncul dalam beberapa detik.

## Perilaku keamanan yang perlu diketahui
- **Opt-in:** tanpa nasabah menyalakan pemberitahuan tidak ada token FCM sama sekali (auto-init dimatikan di manifest).
- **Satu token = satu akun:** bila HP dipakai akun lain, kepemilikan token berpindah; akun lama berhenti menerima ping.
- **Keluar akun / matikan saklar:** token dilepas di server dan dihapus di perangkat. Token yang sudah tidak berlaku
  (aplikasi dihapus) dibersihkan otomatis saat FCM membalas `UNREGISTERED`.
- **Akun nonaktif/dihapus** tidak dibangunkan; token ikut dihapus saat akun dihapus.
- Maksimal 5 perangkat per akun; token disimpan terenkripsi (`APP_KEY`), pencarian memakai hash SHA-256.
- Kegagalan Firebase tidak pernah menggagalkan proses utama (setoran, penarikan, pengumuman); tidak ada token atau isi di log.
- Android 13+: izin notifikasi tetap diminta saat saklar dinyalakan.
