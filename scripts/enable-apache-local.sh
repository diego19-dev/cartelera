#!/usr/bin/env bash
# Instala y activa Apache local (Homebrew httpd + PHP 8.5) para /tv → public/
# Uso: bash scripts/enable-apache-local.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PUBLIC="$ROOT/public"
HTTPD_USER="$(id -un)"
HTTPD_GROUP="$(id -gn)"
HTTPD_CONF="${HOMEBREW_PREFIX:-/home/linuxbrew/.linuxbrew}/etc/httpd/httpd.conf"
EXTRA_CONF="${HOMEBREW_PREFIX:-/home/linuxbrew/.linuxbrew}/etc/httpd/extra/cset-tv.conf"
APACHECTL="${HOMEBREW_PREFIX:-/home/linuxbrew/.linuxbrew}/opt/httpd/bin/apachectl"
PHP_MODULE="${HOMEBREW_PREFIX:-/home/linuxbrew/.linuxbrew}/opt/php/lib/httpd/modules/libphp.so"

if [[ ! -f "$HTTPD_CONF" ]]; then
  echo "Apache (Homebrew) no está instalado. Ejecuta: brew install httpd" >&2
  exit 1
fi

if [[ ! -f "$PHP_MODULE" ]]; then
  echo "Falta libphp.so. Ejecuta: brew install php" >&2
  exit 1
fi

if [[ ! -f "$PUBLIC/.htaccess" ]]; then
  cp "$ROOT/scripts/htaccess.public" "$PUBLIC/.htaccess"
fi

# Apache Homebrew en Linux no tiene usuario _www; debe correr como tu usuario.
sed -i "s/^User _www$/User ${HTTPD_USER}/" "$HTTPD_CONF"
sed -i "s/^Group _www$/Group ${HTTPD_GROUP}/" "$HTTPD_CONF"
sed -i "s/^User www-data$/User ${HTTPD_USER}/" "$HTTPD_CONF"
sed -i "s/^Group www-data$/Group ${HTTPD_GROUP}/" "$HTTPD_CONF"
sed -i 's/^#LoadModule rewrite_module /LoadModule rewrite_module /' "$HTTPD_CONF"
sed -i 's/^#ServerName .*/ServerName localhost/' "$HTTPD_CONF"
if ! grep -q '^ServerName localhost' "$HTTPD_CONF"; then
  sed -i 's/^#ServerName www.example.com:8080$/ServerName localhost/' "$HTTPD_CONF"
fi

cat > "$EXTRA_CONF" <<EOF
# Generado por scripts/enable-apache-local.sh — no versionar
LoadModule php_module ${PHP_MODULE}

<FilesMatch \\.php\$>
    SetHandler application/x-httpd-php
</FilesMatch>

DirectoryIndex index.php index.html

Alias /tv "${PUBLIC}"
<Directory "${PUBLIC}">
    Options FollowSymLinks
    AllowOverride All
    Require all granted
    DirectoryIndex index.php
</Directory>

RedirectMatch 301 ^/cset/?$ /tv/
RedirectMatch 301 ^/cset/(.*)\$ /tv/\$1
EOF

INCLUDE_LINE="Include ${EXTRA_CONF}"
if ! grep -qF "$INCLUDE_LINE" "$HTTPD_CONF"; then
  printf '\n# CSET-TV local\n%s\n' "$INCLUDE_LINE" >> "$HTTPD_CONF"
fi

# Liberar el puerto 8080 si lo ocupa el servidor embebido de PHP
if ss -tln 2>/dev/null | grep -q ':8080 '; then
  pkill -f "php -S .*8080" 2>/dev/null || true
  sleep 1
fi

"$APACHECTL" configtest
if brew services list 2>/dev/null | grep -q '^httpd'; then
  brew services restart httpd
else
  brew services start httpd
fi

sleep 1
echo
echo "Apache local listo."
echo "Cartelera:  http://127.0.0.1:8080/tv/"
echo "Panel:      http://127.0.0.1:8080/tv/login.php"
curl -sI "http://127.0.0.1:8080/tv/" | head -8
curl -sI "http://127.0.0.1:8080/tv/login.php" | head -8
