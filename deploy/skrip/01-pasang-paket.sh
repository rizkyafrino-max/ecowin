#!/usr/bin/env bash
# Memasang Nginx, MySQL, PHP 8.3, Composer, Node.js 24 di Ubuntu 22.04/24.04.
# Aman dijalankan ulang. Tidak menyentuh konfigurasi situs lain dan tidak menyalakan firewall.
# Jalankan: sudo bash deploy/skrip/01-pasang-paket.sh
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
  echo "Jalankan dengan sudo: sudo bash $0" >&2
  exit 1
fi

export DEBIAN_FRONTEND=noninteractive
# Composer menolak berjalan sebagai root tanpa konfirmasi; skrip ini memang berjalan sebagai root.
export COMPOSER_ALLOW_SUPERUSER=1

apt-get update
apt-get install -y --no-upgrade nginx git unzip curl certbot python3-certbot-nginx software-properties-common

# Database: MySQL dan MariaDB saling bertentangan. Jika salah satunya sudah ada, JANGAN memasang yang lain
# (apt akan menghapus yang lama beserta kemungkinan datanya). EcoWin berjalan di keduanya (driver "mysql").
if command -v mariadb >/dev/null || command -v mysql >/dev/null; then
  echo "Server database sudah ada ($(mysql --version)), pemasangan database dilewati."
  DB_UNIT="$(systemctl list-unit-files mariadb.service mysql.service --no-legend 2>/dev/null | awk 'NR==1 {print $1}' || true)"
else
  apt-get install -y --no-upgrade mysql-server
  DB_UNIT=mysql.service
fi

# PHP 8.3: Ubuntu 22.04 belum menyediakannya, jadi pakai repositori ondrej/php (aman dipasang di 24.04 juga).
if ! apt-cache policy php8.3-fpm | grep -q 'Candidate: [0-9]'; then
  add-apt-repository -y ppa:ondrej/php
  apt-get update
fi
apt-get install -y --no-upgrade php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath

# Composer bawaan Ubuntu 22.04 (2.2.x) terlalu lama; salinan resmi di /usr/local/bin didahulukan dalam PATH.
if ! command -v composer >/dev/null || [ "$(composer --version 2>/dev/null | grep -oE '[0-9]+\.[0-9]+' | head -1 | tr -d .)" -lt 26 ]; then
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
  hash -r
fi

# Node.js hanya untuk membangun web nasabah.
if ! command -v node >/dev/null || [ "$(node -p 'process.versions.node.split(".")[0]')" -lt 24 ]; then
  curl -fsSL https://deb.nodesource.com/setup_24.x | bash -
  apt-get install -y --no-upgrade nodejs
fi

systemctl enable --now nginx php8.3-fpm "${DB_UNIT:-mysql.service}"

echo
echo "== Versi terpasang =="
php -v | head -1
composer --version | head -1
node -v
nginx -v 2>&1
mysql --version
