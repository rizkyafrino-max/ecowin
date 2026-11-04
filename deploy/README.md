# Pemasangan EcoWin di VPS Ubuntu 22.04/24.04

Berkas di folder ini menyiapkan server dari kosong sampai berjalan. Panduan lengkap persyaratan ada di `../DEPLOY.md`.

Yang dibutuhkan lebih dulu:
- Akun yang boleh `sudo`.
- Dua nama domain (boleh subdomain) yang A record-nya sudah mengarah ke IP server: satu untuk **API + panel**, satu untuk **web nasabah**.
- Akses ke repositori GitHub. Bila repositori privat, siapkan dulu cara kloningnya (deploy key atau token) oleh Anda sendiri; jangan menaruh token di berkas yang di-commit.

Dalam contoh: `api.contoh.sch.id` dan `web.contoh.sch.id`. Ganti dengan domain Anda.

## Urutan

1. **Ambil kode ke server** (sekali saja):
   ```bash
   sudo mkdir -p /var/www/ecowin && sudo chown "$USER":www-data /var/www/ecowin
   git clone https://github.com/rizkyafrino-max/ecowin.git /var/www/ecowin
   cd /var/www/ecowin
   ```
2. **Pasang paket** (Nginx, MySQL, PHP 8.3, Composer, Node):
   ```bash
   sudo bash deploy/skrip/01-pasang-paket.sh
   ```
3. **Buat database** (sandi acak, disimpan di `/root/ecowin-db-sandi.txt`):
   ```bash
   sudo bash deploy/skrip/02-database.sh
   sudo cat /root/ecowin-db-sandi.txt     # untuk disalin ke .env
   ```
   **Server bersama / root MariaDB berkata sandi:** bila skrip ini ditolak (`Access denied for user 'root'`), minta pemilik server membuat database kosong (mis. `db_ecowin2026`), lalu buat pengguna khusus sekali saja dengan `mariadb -u root -p`:
   ```sql
   CREATE USER 'ecowin'@'localhost' IDENTIFIED BY 'SANDI_KUAT_HURUF_DAN_ANGKA';
   GRANT ALL PRIVILEGES ON db_ecowin2026.* TO 'ecowin'@'localhost'; FLUSH PRIVILEGES;
   ```
   Pakai pengguna itu di `.env` (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), bukan root. Hindari simbol di sandi agar aman dibaca `.env`.
4. **Isi `.env` produksi:**
   ```bash
   cp deploy/env.production.example backend/.env
   nano backend/.env
   chmod 640 backend/.env
   ```
   Ganti `API.DOMAIN-ANDA.ID` dan `WEB.DOMAIN-ANDA.ID`, isi `DB_PASSWORD` dari langkah 3, serta `GOOGLE_CLIENT_ID` dan `GOOGLE_CLIENT_SECRET`.
5. **Rilis** (composer, migrasi, cache, build web):
   ```bash
   bash deploy/skrip/04-rilis.sh api.contoh.sch.id
   ```
6. **Nginx lalu HTTPS:**
   ```bash
   sudo bash deploy/skrip/03-nginx.sh api.contoh.sch.id web.contoh.sch.id
   sudo certbot --nginx -d api.contoh.sch.id -d web.contoh.sch.id --redirect
   ```
7. **Penjadwal** (pengumuman terjadwal, pengingat, pembersihan notifikasi):
   ```bash
   echo '* * * * * www-data cd /var/www/ecowin/backend && php artisan schedule:run >> /dev/null 2>&1' | sudo tee /etc/cron.d/ecowin
   ```
8. **Firebase (notifikasi instan, opsional):** salin berkas JSON akun layanan sendiri ke
   `/var/www/ecowin/backend/storage/app/firebase/akun-layanan.json`, lalu `chmod 640` dan pemilik `www-data` grup. Lihat `../FIREBASE.md`.
9. **Google Cloud:** tambahkan origin dan redirect URI domain baru (`../DEPLOY.md` bagian 2).
10. **Akun admin pertama:** buat lewat `php artisan tinker` atau seeder awal sesuai kebutuhan; jangan jalankan `DemoDataSeeder` di produksi.

Urutan 5 dan 6 boleh ditukar, tetapi `03-nginx.sh` membutuhkan folder `web/dist` hanya saat web dibuka, jadi tidak masalah dijalankan lebih dulu.

## Rilis berikutnya (pembaruan)

Bila folder `deploy/` pernah disalin manual ke server (belum dari Git), hapus dulu sebelum `git pull` agar tidak bentrok: `rm -rf /var/www/ecowin/deploy`. Setelah berkas ini ada di `main`, `04-rilis.sh` sudah membawanya sendiri.

```bash
cd /var/www/ecowin && bash deploy/skrip/04-rilis.sh api.contoh.sch.id
```

## Pengecekan setelah selesai

```bash
curl -sI https://api.contoh.sch.id/up | head -1      # harus 200
curl -sI https://web.contoh.sch.id | head -5
sudo nginx -t && sudo systemctl status php8.3-fpm --no-pager | head -3
```

Lalu ikuti daftar "Sebelum go-live" di `../DEPLOY.md` bagian 7.

## Catatan keamanan
- Skrip tidak menyalakan firewall atau mengubah situs lain di server. Bila ingin firewall (`ufw`), buka SSH (22), 80, dan 443 **sebelum** `ufw enable`.
- `.env`, sandi database, dan berkas Firebase hanya ada di server; jangan dikirim lewat chat atau di-commit.
- Cadangkan database dan `storage/app` (lihat `../DEPLOY.md` bagian 6).
