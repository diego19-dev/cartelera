#!/usr/bin/env bash
# Aplica Alias /tv → /var/www/html/cset/public (requiere sudo).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
sudo cp "$ROOT/bash/apache-cset-alias.conf" /etc/apache2/conf-available/cset-alias.conf
for site in /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-available/smartcset.conf /etc/apache2/sites-enabled/smartcset.conf /etc/apache2/sites-enabled/000-default.conf; do
  [[ -f "$site" ]] || continue
  if grep -q '/var/www/html/cset/public' "$site" || grep -q '/var/www/html/tv/public' "$site"; then
    sudo cp -a "$site" "${site}.bak-src-public-$(date +%Y%m%d%H%M%S)"
    sudo sed -i \
      -e 's|Alias /cset |Alias /tv |g' \
      -e 's|/var/www/html/tv/public|/var/www/html/cset/public|g' \
      -e 's|/var/www/html/cset/public|/var/www/html/cset/public|g' \
      "$site"
    echo "Patched $site"
  elif grep -q 'Alias /cset ' "$site"; then
    sudo cp -a "$site" "${site}.bak-src-public-$(date +%Y%m%d%H%M%S)"
    sudo sed -i -e 's|Alias /cset |Alias /tv |g' "$site"
    echo "Alias /cset → /tv en $site"
  elif grep -q 'Alias /tv ' "$site"; then
    echo "OK (already /tv): $site"
  fi
done
sudo apache2ctl configtest
sudo systemctl reload apache2
echo "Alias /tv → /var/www/html/cset/public"
curl -sI "http://127.0.0.1/tv/api/tv/ping" | head -5
