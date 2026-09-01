#!/usr/bin/env bash
# Aplica Alias /tv → public/ y deja un solo .htaccess.
# Uso: sudo bash /var/www/html/cset/scripts/enable-cset-alias.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PUBLIC="$ROOT/public"
CONF_AVAIL=/etc/apache2/conf-available/cset-alias.conf
CONF_EN=/etc/apache2/conf-enabled/cset-alias.conf

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Ejecuta con sudo: sudo bash $0" >&2
  exit 1
fi

cp "$ROOT/scripts/apache-cset-alias.conf" "$CONF_AVAIL"
ln -sfn "$CONF_AVAIL" "$CONF_EN"

patch_site() {
  local site="$1"
  [[ -f "$site" ]] || return 0
  cp -a "$site" "${site}.bak-tv-alias-$(date +%Y%m%d%H%M%S)"
  sed -i \
    -e 's|Alias /cset |Alias /tv |g' \
    -e 's|/var/www/html/cset/src/public|/var/www/html/cset/public|g' \
    -e 's|/var/www/html/tv/public|/var/www/html/cset/public|g' \
    -e 's|/var/www/html/cset/www|/var/www/html/cset/public|g' \
    "$site"
  # Delimitador # (el patrón viejo contiene | y rompe sed con s|...|)
  sed -i \
    -e 's#RedirectMatch permanent \^/cset\$ https://10.5.213.111/cset#RedirectMatch 301 ^/cset/?$ https://10.5.213.111/tv/#g' \
    -e 's#RedirectMatch permanent \^/cset/(?!api/|assets/)\(.*\)\$ https://10.5.213.111/cset/\$1#RedirectMatch 301 ^/cset/(.*)$ https://10.5.213.111/tv/$1#g' \
    "$site"
  if ! grep -q 'Alias /tv ' "$site"; then
    python3 - "$site" <<'PY'
import sys
from pathlib import Path
path = Path(sys.argv[1])
text = path.read_text()
block = '''
    # CSET: Alias /tv → public/ (único .htaccess)
    Alias /tv /var/www/html/cset/public
    <Directory /var/www/html/cset/public>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
    </Directory>
    # Unificar URLs: /cset → /tv
    RedirectMatch 301 ^/cset/?$ /tv/
    RedirectMatch 301 ^/cset/(.*)$ /tv/$1
'''
needle = 'DocumentRoot /var/www/html\n'
if needle not in text:
    print(f'AVISO: no se insertó Alias en {path}')
    raise SystemExit(0)
parts = text.split(needle)
out = parts[0] + ''.join(needle + block + p for p in parts[1:])
path.write_text(out)
print(f'Insertado Alias /tv en {path}')
PY
  fi
  echo "Actualizado $site"
}

for site in /etc/apache2/sites-available/000-default.conf \
            /etc/apache2/sites-available/smartcset.conf \
            /etc/apache2/sites-enabled/000-default.conf \
            /etc/apache2/sites-enabled/smartcset.conf \
            /etc/apache2/sites-enabled/smartcset-ssl.conf; do
  patch_site "$site"
done

cp "$ROOT/scripts/htaccess.public" "$PUBLIC/.htaccess"
rm -f "$ROOT/.htaccess" \
      "$PUBLIC/api/.htaccess" \
      "$PUBLIC/assets/uploads/.htaccess" \
      "$ROOT/backups/.htaccess" \
      "$ROOT/img/.htaccess"
find "$ROOT" -name '.htaccess' \
  -not -path "$PUBLIC/.htaccess" \
  -not -path '*/vendor/*' \
  -not -path '*/.build-tmp/*' \
  -delete 2>/dev/null || true

apache2ctl configtest
systemctl reload apache2

if [ -L /var/www/html/tv ]; then
  rm -f /var/www/html/tv
  echo "Eliminado symlink /var/www/html/tv"
fi

echo
echo "Listo. Solo debe existir: $PUBLIC/.htaccess"
# VHost de /tv está en 10.5.213.103 (no en el default 127.0.0.1)
HOST_HDR=(-H 'Host: 10.5.213.103')
curl -sI "${HOST_HDR[@]}" "http://127.0.0.1/tv/" | head -5
curl -sI "${HOST_HDR[@]}" "http://127.0.0.1/tv/login.php" | head -5
curl -sI "${HOST_HDR[@]}" "http://127.0.0.1/tv/api/tv/ping" | head -5
