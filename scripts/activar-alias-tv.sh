#!/usr/bin/env bash
# =============================================================================
# Configuración del alias Apache HTTPD (/tv -> raíz del repositorio)
# Permite servir la cartelera institucional en entornos de producción con Apache.
# Uso: sudo bash scripts/activar-alias-tv.sh
# =============================================================================
Aplica Alias /tv → raíz del repo y deja un solo .htaccess.
# Uso: sudo bash /var/www/html/Cartelera/scripts/activar-alias-tv.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CONF_AVAIL=/etc/apache2/conf-available/cset-alias.conf
CONF_EN=/etc/apache2/conf-enabled/cset-alias.conf

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Ejecuta con sudo: sudo bash $0" >&2
  exit 1
fi

cp "$ROOT/scripts/apache-alias-tv.conf" "$CONF_AVAIL"
ln -sfn "$CONF_AVAIL" "$CONF_EN"

patch_site() {
  local site="$1"
  [[ -f "$site" ]] || return 0
  cp -a "$site" "${site}.bak-tv-alias-$(date +%Y%m%d%H%M%S)"
  sed -i \
    -e 's|Alias /cset |Alias /tv |g' \
    -e 's|/var/www/html/Cartelera/public|/var/www/html/Cartelera|g' \
    -e 's|/var/www/html/cset/src/public|/var/www/html/Cartelera|g' \
    -e 's|/var/www/html/tv/public|/var/www/html/Cartelera|g' \
    -e 's|/var/www/html/cset/www|/var/www/html/Cartelera|g' \
    -e 's|/var/www/html/cset/public|/var/www/html/Cartelera|g' \
    "$site"
  if ! grep -q 'Alias /tv ' "$site"; then
    python3 - "$site" <<'PY'
import sys
from pathlib import Path
path = Path(sys.argv[1])
text = path.read_text()
block = '''
    # Cartelera: Alias /tv → raíz del repo
    Alias /tv /var/www/html/Cartelera
    <Directory /var/www/html/Cartelera>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
    </Directory>
    RedirectMatch 301 ^/cset/?$ /tv/
    RedirectMatch 301 ^/cset/(.*)$ /tv/$1
    RedirectMatch 301 ^/Cartelera/?$ /tv/
    RedirectMatch 301 ^/Cartelera/public/?$ /tv/
    RedirectMatch 301 ^/Cartelera/public/(.*)$ /tv/$1
    RedirectMatch 301 ^/Cartelera/(.*)$ /tv/$1
'''
needle = 'DocumentRoot /var/www/html\n'
if needle not in text:
    print(f'AVISO: no se insertó Alias en {path}')
    raise SystemExit(0)
parts = text.split(needle)
path.write_text(parts[0] + ''.join(needle + block + p for p in parts[1:]))
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

bash "$ROOT/scripts/preparar.sh"
apache2ctl configtest
systemctl reload apache2

echo
echo "Listo. Alias /tv → $ROOT"
curl -sI -H 'Host: 10.5.213.103' "http://127.0.0.1/tv/" | head -5 || true
