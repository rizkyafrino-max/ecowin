#!/usr/bin/env bash
# Membuat SATU berkas "paket pindah" terenkripsi untuk memindahkan EcoWin ke VPS lain atau memulihkannya dari nol.
# Isi paket: dump database, seluruh storage/app (foto, kartu, KUNCI FIREBASE), backend/.env (berisi rahasia), dan MANIFEST.
# Karena berisi rahasia, paket dienkripsi (AES-256) dengan kata sandi yang Anda ketik. CATAT kata sandinya: tanpa itu paket tidak bisa dibuka.
# Dijalankan manual sebagai pengguna biasa pemilik .env (bukan root); butuh terminal interaktif untuk mengetik kata sandi.
# Jalankan: bash deploy/skrip/06-paket-pindah.sh
set -euo pipefail

APP="${ECOWIN_APP:-/var/www/ecowin/backend}"
DEST="${ECOWIN_PINDAH_DIR:-$HOME/paket-pindah}"

if [ "$(id -u)" -eq 0 ]; then
  echo "Jalankan sebagai pengguna biasa pemilik .env, bukan root." >&2
  exit 1
fi
[ -r "$APP/.env" ] || { echo ".env tidak terbaca di $APP" >&2; exit 1; }
for alat in openssl mysqldump tar gzip sha256sum; do
  command -v "$alat" >/dev/null || { echo "Alat '$alat' belum terpasang." >&2; exit 1; }
done

nilai() { grep -E "^$1=" "$APP/.env" | tail -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'; }
DB="$(nilai DB_DATABASE)"
USR="$(nilai DB_USERNAME)"
PW="$(nilai DB_PASSWORD)"
HOST="$(nilai DB_HOST)"
PORT="$(nilai DB_PORT)"
if [ -z "$DB" ] || [ -z "$USR" ]; then
  echo "DB_DATABASE atau DB_USERNAME kosong di .env" >&2
  exit 1
fi

umask 077
mkdir -p "$DEST"
STAMP="$(date +%Y%m%d-%H%M%S)"
KERJA="$(mktemp -d)"
trap 'rm -rf "$KERJA"; rm -f "$DEST"/*.tmp' EXIT

# Sandi database lewat berkas sementara (bukan argumen perintah) supaya tidak terlihat di daftar proses.
printf '[client]\nuser=%s\npassword="%s"\nhost=%s\nport=%s\n' "$USR" "$PW" "${HOST:-127.0.0.1}" "${PORT:-3306}" > "$KERJA/my.cnf"

mkdir "$KERJA/paket"
echo "Membuat dump database..."
mysqldump --defaults-extra-file="$KERJA/my.cnf" --single-transaction --quick --default-character-set=utf8mb4 "$DB" \
  | gzip > "$KERJA/paket/database.sql.gz"
gzip -t "$KERJA/paket/database.sql.gz"

echo "Mengumpulkan berkas unggahan dan pengaturan..."
tar -czf "$KERJA/paket/storage-app.tar.gz" -C "$APP/storage" app
cp "$APP/.env" "$KERJA/paket/env"

{
  echo "EcoWin paket pindah"
  echo "dibuat      : $(date -Iseconds)"
  echo "commit kode : $(git -C "$APP/.." rev-parse HEAD 2>/dev/null || echo tidak-diketahui)"
  echo "APP_URL     : $(nilai APP_URL)"
  echo "web         : $(nilai ECOWIN_WEB_URL)"
  echo "database    : $DB (pengguna $USR)"
  echo "php         : $(php -v 2>/dev/null | head -1 || echo tidak-diketahui)"
} > "$KERJA/paket/MANIFEST.txt"

tar -czf "$KERJA/paket.tar.gz" -C "$KERJA" paket

echo
echo "Ketik kata sandi untuk mengenkripsi paket (dua kali). CATAT kata sandinya; tanpa itu paket tidak bisa dibuka."
BERKAS="$DEST/ecowin-pindah-$STAMP.tar.gz.enc"
openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -in "$KERJA/paket.tar.gz" -out "$BERKAS.tmp"
mv "$BERKAS.tmp" "$BERKAS"
( cd "$DEST" && sha256sum "$(basename "$BERKAS")" > "$(basename "$BERKAS").sha256" )

echo
echo "Paket pindah selesai:"
ls -lh "$BERKAS" "$BERKAS.sha256"
echo
echo "Langkah berikutnya: salin berkas .enc dan .sha256 itu ke komputer Anda / penyimpanan lain (jangan hanya di server ini)."
echo "Pemulihan di VPS baru: lihat deploy/README.md bagian \"Pindah ke VPS lain\"."
