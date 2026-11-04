#!/usr/bin/env bash
# Mengambil kode terbaru, memasang dependensi, migrasi, cache, dan membangun web nasabah.
# Dijalankan sebagai pengguna biasa (bukan root) yang boleh sudo. Aman diulang di setiap rilis.
# Jalankan: bash deploy/skrip/04-rilis.sh api.contoh.sch.id
set -euo pipefail

if [ "$(id -u)" -eq 0 ]; then
  echo "Jalankan sebagai pengguna biasa, bukan root (sudo hanya dipanggil bila perlu)." >&2
  exit 1
fi
if [ $# -ne 1 ]; then
  echo "Pemakaian: bash $0 DOMAIN_API" >&2
  exit 1
fi

API="$1"
DIR=/var/www/ecowin
REPO=https://github.com/rizkyafrino-max/ecowin.git

if [ ! -d "$DIR/.git" ]; then
  sudo mkdir -p "$DIR"
  sudo chown "$USER":www-data "$DIR"
  git clone "$REPO" "$DIR"
else
  git -C "$DIR" pull --ff-only
fi

cd "$DIR/backend"
if [ ! -f .env ]; then
  echo ".env belum ada. Salin dulu: cp $DIR/deploy/env.production.example $DIR/backend/.env" >&2
  echo "lalu isi nilai bertanda ISI_ dan domain, kemudian jalankan skrip ini lagi." >&2
  exit 1
fi
if grep -vE '^[[:space:]]*#' .env | grep -qE 'ISI_|DOMAIN-ANDA'; then
  echo ".env masih berisi nilai contoh (ISI_ / DOMAIN-ANDA). Lengkapi dulu." >&2
  exit 1
fi

composer install --no-dev --optimize-autoloader --no-interaction
grep -qE '^APP_KEY=.+' .env || php artisan key:generate --force

# Folder yang harus bisa ditulis server web.
sudo mkdir -p storage/app/private storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
sudo chown -R "$USER":www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod g+s {} +

php artisan migrate --force
php artisan storage:link || true
php artisan optimize
php artisan filament:optimize

# Web nasabah
cd "$DIR/web"
echo "VITE_API_BASE_URL=https://$API/api" > .env.production
npm ci
npm run build

sudo systemctl reload php8.3-fpm
echo
echo "Rilis selesai. Untuk jadwal otomatis, pastikan cron terpasang (lihat deploy/README.md langkah 7)."
