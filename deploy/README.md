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

## Cadangan harian

`skrip/05-cadangan.sh` menyimpan dump database (`db-*.sql.gz`) dan berkas unggahan `storage/app` tanpa kunci Firebase (`berkas-*.tar.gz`) ke `~/cadangan-ecowin`, 14 hari terakhir. Sandi database dibaca dari `backend/.env`; jalankan sebagai pengguna biasa pemilik `.env`, bukan root.

```bash
cp /var/www/ecowin/deploy/skrip/05-cadangan.sh ~/ && bash ~/05-cadangan.sh     # uji sekali
(crontab -l 2>/dev/null; echo '30 2 * * * bash /home/rizki/05-cadangan.sh >> /home/rizki/cadangan-ecowin/log.txt 2>&1') | crontab -
```

Ganti `rizki` dengan nama pengguna Anda. Salin cadangan terbaru ke komputer lain secara berkala (cadangan di server yang sama tidak menolong bila server rusak). Dari laptop:

```powershell
scp -P 1610 rizki@IP-SERVER:/home/rizki/cadangan-ecowin/db-*.sql.gz .
```

Pulihkan: `gunzip -c db-AAAAMMDD-JJMMSS.sql.gz | mysql -u ecowin -p NAMA_DATABASE`. Berkas cadangan berisi data nasabah: jangan di-commit dan jangan dibagikan.

## Pindah ke VPS lain (atau pulihkan dari nol)

Cadangan harian (`05-cadangan.sh`) hanya berisi database dan foto. Untuk **memindahkan seluruh EcoWin** ke VPS lain, buat satu **paket pindah** terenkripsi yang berisi database, seluruh `storage/app` (termasuk kunci Firebase), dan `backend/.env`. Paket ini berisi rahasia, jadi dienkripsi (AES-256) dengan kata sandi yang Anda ketik dan **harus dicatat**: tanpa kata sandinya paket tidak bisa dibuka. Skrip sudah diuji dari awal sampai akhir (cadangkan, enkripsi, dekripsi, pulihkan ke database lain, data identik).

**A. Di server lama** (sebagai pengguna biasa pemilik `.env`, terminal interaktif):
```bash
bash /var/www/ecowin/deploy/skrip/06-paket-pindah.sh
```
Skrip meminta kata sandi dua kali, lalu menghasilkan `~/paket-pindah/ecowin-pindah-AAAAMMDD-JJMMSS.tar.gz.enc` dan berkas `.sha256`-nya. **Segera salin keduanya ke komputer Anda** (jangan hanya di server): dari laptop,
```powershell
scp -P 1610 "rizki@IP-SERVER:/home/rizki/paket-pindah/ecowin-pindah-*" D:\paket-pindah\
```
Buat paket baru setiap kali data banyak berubah (misalnya seminggu sekali); paket hanya berisi data sampai saat dibuat.

**B. Di VPS baru** (Ubuntu 22.04/24.04, akun yang boleh `sudo`):
1. Ikuti **Urutan** langkah 1 sampai 3 di atas: clone kode, `01-pasang-paket.sh`, dan `02-database.sh` (database kosong dengan pengguna dan sandi baru; jangan isi `.env` dulu).
2. Kirim paket ke VPS baru (dari laptop): `scp -P PORT D:\paket-pindah\ecowin-pindah-* pengguna@IP-VPS-BARU:~/`
3. Pulihkan:
   ```bash
   cd /var/www/ecowin
   bash deploy/skrip/07-pulihkan.sh ~/ecowin-pindah-AAAAMMDD-JJMMSS.tar.gz.enc
   ```
   Skrip memeriksa keutuhan paket, meminta kata sandi paket, menolak berjalan bila database tujuan sudah berisi tabel atau `.env` sudah ada (tidak menimpa apa pun tanpa `ECOWIN_TIMPA=1`), memasang `.env` lama dengan **pengaturan database VPS baru**, mengimpor database, dan mengembalikan berkas unggahan beserta izinnya.
4. Lanjutkan seperti pemasangan biasa: `bash deploy/skrip/04-rilis.sh API_DOMAIN`, `sudo bash deploy/skrip/03-nginx.sh API_DOMAIN WEB_DOMAIN`, arahkan **A record** domain ke IP VPS baru, lalu `sudo certbot --nginx -d API_DOMAIN -d WEB_DOMAIN --redirect`. Pasang cron (langkah 7) dan cadangan harian.
5. Uji: buka web dan `/admin`, masuk dengan Google, dan pastikan data nasabah dan foto tampil.

**Penting:**
- Coba pemulihan di VPS uji **sebelum** server lama dimatikan, dan jangan memindahkan DNS sebelum hasilnya diperiksa.
- Bila server lama diduga diretas, anggap seluruh rahasia di dalam paket bocor: setelah pemulihan, buat **Google Client Secret baru** (hapus yang lama), ganti `GOOGLE_CLIENT_SECRET` di `.env`, dan pertimbangkan membuat `APP_KEY` baru (`php artisan key:generate --force`; akibatnya semua pengguna harus masuk lagi dan token notifikasi terdaftar ulang). Jangan memulihkan berkas apa pun dari server yang diretas selain paket ini.
- Data nasabah bersifat pribadi: simpan paket dan kata sandinya di tempat berbeda, dan jangan dikirim lewat chat atau di-commit.

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
