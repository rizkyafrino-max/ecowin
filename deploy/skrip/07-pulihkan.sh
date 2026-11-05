#!/usr/bin/env bash
# Memulihkan "paket pindah" (hasil 06-paket-pindah.sh) di VPS baru: pengaturan (.env), database, dan berkas unggahan.
# Dijalankan SETELAH di VPS baru: kode di-clone, 01-pasang-paket.sh, dan 02-database.sh (database kosong + pengguna baru).
# Sandi database baru dibaca dari /root/ecowin-db-sandi.txt (dibuat 02-database.sh) dan dimasukkan ke .env hasil pemulihan.
# Dijalankan sebagai pengguna biasa yang boleh sudo (bukan root); butuh terminal interaktif untuk kata sandi paket.
# Jalankan: bash deploy/skrip/07-pulihkan.sh /jalur/ecowin-pindah-AAAAMMDD-JJMMSS.tar.gz.enc
set -euo pipefail

APP="${ECOWIN_APP:-/var/www/ecowin/backend}"
SANDI_BERKAS="${ECOWIN_SANDI_DB_BERKAS:-/root/ecowin-db-sandi.txt}"
DB_PORT_BARU="${ECOWIN_DB_PORT:-3306}" # database di VPS baru berada di komputer yang sama

if [ "$(id -u)" -eq 0 ]; then
  echo "Jalankan sebagai pengguna biasa yang boleh sudo, bukan root." >&2
  exit 1
fi
if [ $# -ne 1 ] || [ ! -f "$1" ]; then
  echo "Pemakaian: bash $0 /jalur/ke/ecowin-pindah-XXXX.tar.gz.enc" >&2
  exit 1
fi
PAKET="$1"
[ -d "$APP" ] || { echo "Folder $APP tidak ada. Clone kode ke /var/www/ecowin lebih dulu." >&2; exit 1; }
if [ -e "$APP/.env" ] && [ "${ECOWIN_TIMPA:-0}" != "1" ]; then
  echo "$APP/.env sudah ada. Hentikan agar tidak tertimpa. Kalau memang ingin menimpa: ECOWIN_TIMPA=1 bash $0 ..." >&2
  exit 1
fi
for alat in openssl mysql tar gzip sha256sum; do
  command -v "$alat" >/dev/null || { echo "Alat '$alat' belum terpasang." >&2; exit 1; }
done

if [ -f "$PAKET.sha256" ]; then
  echo "Memeriksa keutuhan paket..."
  ( cd "$(dirname "$PAKET")" && sha256sum -c "$(basename "$PAKET").sha256" )
fi

# Sandi database yang dibuat 02-database.sh di VPS ini.
baca_sandi() {
  if [ -r "$SANDI_BERKAS" ]; then cat "$SANDI_BERKAS"
  elif sudo test -f "$SANDI_BERKAS" 2>/dev/null; then sudo cat "$SANDI_BERKAS"
  else return 1
  fi
}
SANDI_TXT="$(baca_sandi)" || { echo "Tidak menemukan $SANDI_BERKAS. Jalankan dulu: sudo bash deploy/skrip/02-database.sh" >&2; exit 1; }
ambil() { printf '%s\n' "$SANDI_TXT" | grep -E "^$1=" | tail -1 | cut -d= -f2-; }
BARU_DB="$(ambil DB_DATABASE)"; BARU_USR="$(ambil DB_USERNAME)"; BARU_PW="$(ambil DB_PASSWORD)"
[ -n "$BARU_DB" ] && [ -n "$BARU_USR" ] && [ -n "$BARU_PW" ] || { echo "Isi $SANDI_BERKAS tidak lengkap." >&2; exit 1; }

umask 077
KERJA="$(mktemp -d)"
trap 'rm -rf "$KERJA"' EXIT

echo
echo "Ketik kata sandi paket (yang dibuat saat 06-paket-pindah.sh)."
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -in "$PAKET" -out "$KERJA/paket.tar.gz" \
  || { echo "Kata sandi salah atau paket rusak." >&2; exit 1; }
tar -xzf "$KERJA/paket.tar.gz" -C "$KERJA"
for f in database.sql.gz storage-app.tar.gz env MANIFEST.txt; do
  [ -f "$KERJA/paket/$f" ] || { echo "Paket tidak lengkap: $f tidak ada." >&2; exit 1; }
done

echo
echo "== Isi paket =="
cat "$KERJA/paket/MANIFEST.txt"
echo

# Periksa dulu database tujuan (sebelum menulis apa pun): harus kosong kecuali dipaksa.
printf '[client]\nuser=%s\npassword="%s"\nhost=127.0.0.1\nport=%s\n' "$BARU_USR" "$BARU_PW" "$DB_PORT_BARU" > "$KERJA/my.cnf"
JUMLAH="$(mysql --defaults-extra-file="$KERJA/my.cnf" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$BARU_DB'")"
if [ "$JUMLAH" != "0" ] && [ "${ECOWIN_TIMPA:-0}" != "1" ]; then
  echo "Database $BARU_DB sudah berisi $JUMLAH tabel. Dihentikan sebelum mengubah apa pun (ECOWIN_TIMPA=1 untuk memaksa)." >&2
  exit 1
fi

# 1) .env: pakai milik server lama, tetapi database mengikuti VPS baru.
cp "$KERJA/paket/env" "$APP/.env"
chmod 640 "$APP/.env"
tulis_env() { # kunci nilai (nilai berupa huruf dan angka saja)
  if grep -qE "^$1=" "$APP/.env"; then sed -i "s|^$1=.*|$1=$2|" "$APP/.env"; else printf '%s=%s\n' "$1" "$2" >> "$APP/.env"; fi
}
tulis_env DB_DATABASE "$BARU_DB"
tulis_env DB_USERNAME "$BARU_USR"
tulis_env DB_PASSWORD "$BARU_PW"
tulis_env DB_HOST 127.0.0.1
tulis_env DB_PORT "$DB_PORT_BARU"
echo ".env dipulihkan (pengaturan database disesuaikan dengan VPS ini)."

# 2) Database.
echo "Mengimpor database..."
gunzip -c "$KERJA/paket/database.sql.gz" | mysql --defaults-extra-file="$KERJA/my.cnf" --default-character-set=utf8mb4 "$BARU_DB"
echo "Database dipulihkan: $(mysql --defaults-extra-file="$KERJA/my.cnf" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$BARU_DB'") tabel."

# 3) Berkas unggahan (termasuk kunci Firebase) dan izin folder.
mkdir -p "$APP/storage"
tar -xzf "$KERJA/paket/storage-app.tar.gz" -C "$APP/storage"
if getent group www-data >/dev/null 2>&1; then
  sudo chown -R "$USER":www-data "$APP/storage" 2>/dev/null || true
  sudo chmod -R ug+rwX "$APP/storage" 2>/dev/null || true
  [ -d "$APP/storage/app/firebase" ] && sudo chmod 640 "$APP/storage/app/firebase"/* 2>/dev/null || true
fi
echo "Berkas unggahan dipulihkan."

echo
echo "Pemulihan selesai. Lanjutkan di VPS baru:"
echo "  1) bash deploy/skrip/04-rilis.sh DOMAIN_API       (dependensi, cache, build web)"
echo "  2) sudo bash deploy/skrip/03-nginx.sh DOMAIN_API DOMAIN_WEB"
echo "  3) sudo certbot --nginx -d DOMAIN_API -d DOMAIN_WEB --redirect   (setelah A record domain mengarah ke VPS ini)"
echo "  4) pasang cron (README langkah 7) dan cadangan harian (bagian Cadangan harian)"
echo "Disarankan: ganti rahasia yang ikut terbawa (Google Client Secret, APP_KEY bila perlu) bila server lama tidak tepercaya."
