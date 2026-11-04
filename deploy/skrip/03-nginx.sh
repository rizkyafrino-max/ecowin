#!/usr/bin/env bash
# Memasang konfigurasi Nginx untuk API/panel dan web nasabah dengan domain Anda.
# Jalankan: sudo bash deploy/skrip/03-nginx.sh api.contoh.sch.id web.contoh.sch.id
# Situs lain di server tidak diubah; kedua berkas memakai server_name sendiri.
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
  echo "Jalankan dengan sudo: sudo bash $0 DOMAIN_API DOMAIN_WEB" >&2
  exit 1
fi
if [ $# -ne 2 ]; then
  echo "Pemakaian: sudo bash $0 DOMAIN_API DOMAIN_WEB" >&2
  exit 1
fi

API="$1"
WEB="$2"
DOMAIN_RE='^([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$'
for d in "$API" "$WEB"; do
  if ! [[ "$d" =~ $DOMAIN_RE ]]; then
    echo "Domain tidak valid: $d (gunakan huruf kecil, mis. api.contoh.sch.id)" >&2
    exit 1
  fi
done

SUMBER="$(cd "$(dirname "$0")/../nginx" && pwd)"

for nama in api web; do
  tujuan="/etc/nginx/sites-available/ecowin-$nama"
  if [ -e "$tujuan" ]; then
    cp "$tujuan" "$tujuan.bak.$(date +%Y%m%d%H%M%S)"
  fi
done

sed "s/API\.DOMAIN-ANDA\.ID/$API/g" "$SUMBER/ecowin-api.conf" > /etc/nginx/sites-available/ecowin-api
sed "s/WEB\.DOMAIN-ANDA\.ID/$WEB/g" "$SUMBER/ecowin-web.conf" > /etc/nginx/sites-available/ecowin-web
ln -sf /etc/nginx/sites-available/ecowin-api /etc/nginx/sites-enabled/ecowin-api
ln -sf /etc/nginx/sites-available/ecowin-web /etc/nginx/sites-enabled/ecowin-web

nginx -t
systemctl reload nginx
echo "Nginx siap untuk $API dan $WEB (masih HTTP; HTTPS dipasang di langkah berikutnya)."
