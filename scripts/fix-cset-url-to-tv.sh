#!/usr/bin/env bash
# En el servidor de producción (10.5.213.111):
#   sudo bash /var/www/html/cset/bash/fix-cset-url-to-tv.sh
#
# Cambia redirects que mantienen /cset → /tv y asegura Alias /tv.
# Resultado: http(s)://10.5.213.111/cset/... → https://10.5.213.111/tv/...
set -euo pipefail

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Ejecuta con sudo: sudo bash $0" >&2
  exit 1
fi

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
HOST_IP="${CSET_PUBLIC_HOST:-10.5.213.111}"
stamp="$(date +%Y%m%d%H%M%S)"

export HOST_IP stamp

python3 - <<'PY'
import os, re, shutil
from pathlib import Path

host = os.environ["HOST_IP"]
stamp = os.environ["stamp"]
roots = [
    Path("/etc/apache2/sites-available"),
    Path("/etc/apache2/sites-enabled"),
    Path("/etc/apache2/conf-available"),
    Path("/etc/apache2/conf-enabled"),
]

redir_block = f"""    RedirectMatch 301 ^/cset/?$ https://{host}/tv/
    RedirectMatch 301 ^/cset/(.*)$ https://{host}/tv/$1
"""

alias_block = f"""
    # CSET: URL canónica /tv (bookmarks /cset → /tv)
    Alias /tv /var/www/html/cset/public
    <Directory /var/www/html/cset/public>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.html
    </Directory>
{redir_block}"""

patterns = [
    # HTTP→HTTPS antiguo que conservaba /cset
    (re.compile(r"RedirectMatch\s+permanent\s+\^/cset\(\.\*\)\$\s+https://10\.5\.213\.111/cset\$1"), redir_block.rstrip()),
    (re.compile(r"RedirectMatch\s+permanent\s+\^/cset\$\s+https://10\.5\.213\.111/cset"), f"RedirectMatch 301 ^/cset/?$ https://{host}/tv/"),
    (re.compile(r"RedirectMatch\s+permanent\s+\^/cset/\(\?!api/\|assets/\)\(\.\*\)\$\s+https://10\.5\.213\.111/cset/\$1"), f"RedirectMatch 301 ^/cset/(.*)$ https://{host}/tv/$1"),
    (re.compile(r"Alias /cset "), "Alias /tv "),
    (re.compile(r"/var/www/html/cset/public"), "/var/www/html/cset/public"),
    (re.compile(r"/var/www/html/tv/public"), "/var/www/html/cset/public"),
]

seen = set()
for root in roots:
    if not root.is_dir():
        continue
    for path in root.glob("*.conf"):
        if path.is_symlink():
            continue
        key = path.resolve()
        if key in seen:
            continue
        seen.add(key)
        text = path.read_text()
        if not re.search(r"cset|/tv\b|Alias /cset|Alias /tv", text):
            continue
        orig = text
        for rx, repl in patterns:
            text = rx.sub(repl, text)
        if "Alias /tv " not in text and "DocumentRoot /var/www/html\n" in text:
            text = text.replace("DocumentRoot /var/www/html\n", "DocumentRoot /var/www/html\n" + alias_block, 1)
        elif "Alias /tv " in text and "RedirectMatch 301 ^/cset" not in text and "RedirectMatch permanent ^/cset" not in text:
            marker = "DirectoryIndex index.html\n    </Directory>\n"
            if marker in text:
                text = text.replace(marker, marker + "\n" + redir_block, 1)
        if text != orig:
            shutil.copy2(path, f"{path}.bak-cset-to-tv-{stamp}")
            path.write_text(text)
            print(f"Patched {path}")
        else:
            print(f"OK (sin cambios de URL): {path}")
PY

cp "$ROOT/bash/apache-cset-alias.conf" /etc/apache2/conf-available/cset-alias.conf
ln -sfn /etc/apache2/conf-available/cset-alias.conf /etc/apache2/conf-enabled/cset-alias.conf

apache2ctl configtest
systemctl reload apache2

echo
echo "Verificación en ${HOST_IP}:"
curl -sI "http://${HOST_IP}/cset/" | head -8 || true
curl -sI "http://${HOST_IP}/cset/login" | head -8 || true
curl -skI "https://${HOST_IP}/cset/login" | head -8 || true
curl -skI "https://${HOST_IP}/tv/login" | head -8 || true
