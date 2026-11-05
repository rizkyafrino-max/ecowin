#!/usr/bin/env bash
# Cadangan database EcoWin + berkas unggahan (storage/app, tanpa kunci Firebase). Menyimpan 14 hari terakhir.
# Dijalankan sebagai pengguna biasa pemilik backend/.env (bukan root); sandi database dibaca dari .env.
# Jalankan: bash deploy/skrip/05-cadangan.sh        Pasang cron harian: lihat deploy/README.md.
set -euo pipefail

APP=/var/www/ecowin/backend
DEST="${ECOWIN_CADANGAN_DIR:-$HOME/cadangan-ecowin}"
SIMPAN_HARI=14

if [ "$(id -u)" -eq 0 ]; then
  echo "Jalankan sebagai pengguna biasa pemilik .env, bukan root." >&2
  exit 1
fi
[ -r "$APP/.env" ] || { echo ".env tidak terbaca di $APP" >&2; exit 1; }

nilai() { grep -E "^$1=" "$APP/.env" | tail -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'; }
DB="$(nilai DB_DATABASE)"
USR="$(nilai DB_USERNAME)"
PW="$(nilai DB_PASSWORD)"
if [ -z "$DB" ] || [ -z "$USR" ]; then
  echo "DB_DATABASE atau DB_USERNAME kosong di .env" >&2
  exit 1
fi

umask 077
mkdir -p "$DEST"
STAMP="$(date +%Y%m%d-%H%M%S)"

# Sandi lewat berkas sementara (bukan argumen perintah) supaya tidak terlihat di daftar proses.
CNF="$(mktemp)"
trap 'rm -f "$CNF" "$DEST"/*.tmp' EXIT
printf '[client]\nuser=%s\npassword="%s"\nhost=127.0.0.1\n' "$USR" "$PW" > "$CNF"

mysqldump --defaults-extra-file="$CNF" --single-transaction --quick --default-character-set=utf8mb4 "$DB" \
  | gzip > "$DEST/db-$STAMP.sql.gz.tmp"
gzip -t "$DEST/db-$STAMP.sql.gz.tmp"
mv "$DEST/db-$STAMP.sql.gz.tmp" "$DEST/db-$STAMP.sql.gz"

tar -czf "$DEST/berkas-$STAMP.tar.gz.tmp" --exclude='app/firebase' -C "$APP/storage" app
mv "$DEST/berkas-$STAMP.tar.gz.tmp" "$DEST/berkas-$STAMP.tar.gz"

find "$DEST" -type f \( -name 'db-*.sql.gz' -o -name 'berkas-*.tar.gz' \) -mtime +"$SIMPAN_HARI" -delete

echo "Cadangan selesai di $DEST:"
ls -lh "$DEST/db-$STAMP.sql.gz" "$DEST/berkas-$STAMP.tar.gz"
