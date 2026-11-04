#!/usr/bin/env bash
# Membuat database "ecowin" dan pengguna "ecowin" dengan sandi acak.
# Sandi disimpan di /root/ecowin-db-sandi.txt (hanya bisa dibaca root) dan TIDAK dicetak ke layar.
# Jalankan: sudo bash deploy/skrip/02-database.sh
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
  echo "Jalankan dengan sudo: sudo bash $0" >&2
  exit 1
fi

BERKAS=/root/ecowin-db-sandi.txt
if [ -e "$BERKAS" ]; then
  echo "Sudah ada $BERKAS, skrip berhenti agar sandi lama tidak tertimpa." >&2
  echo "Hapus berkas itu (dan pengguna MySQL 'ecowin') bila memang ingin mengulang." >&2
  exit 1
fi

SANDI="$(openssl rand -base64 33 | tr -dc 'A-Za-z0-9' | head -c 32)"

mysql <<SQL
CREATE DATABASE IF NOT EXISTS ecowin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'ecowin'@'localhost' IDENTIFIED BY '${SANDI}';
GRANT ALL PRIVILEGES ON ecowin.* TO 'ecowin'@'localhost';
FLUSH PRIVILEGES;
SQL

umask 077
printf 'DB_DATABASE=ecowin\nDB_USERNAME=ecowin\nDB_PASSWORD=%s\n' "$SANDI" > "$BERKAS"
echo "Database siap. Sandi tersimpan di $BERKAS (lihat dengan: sudo cat $BERKAS)."
