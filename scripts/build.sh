#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PUBLIC="$ROOT/public"

mkdir -p "$PUBLIC/assets/uploads"/{academic,center,general,branding,welcome,gallery,media}

if [ ! -f "$PUBLIC/api/index.php" ]; then
  echo "ERROR: falta public/api/index.php" >&2
  exit 1
fi

if [ ! -f "$PUBLIC/signage/index.html" ]; then
  echo "ERROR: falta public/signage/index.html (player ES5)" >&2
  exit 1
fi

cp "$ROOT/scripts/htaccess.public" "$PUBLIC/.htaccess"
rm -f "$PUBLIC/api/.htaccess" "$PUBLIC/assets/uploads/.htaccess" "$ROOT/.htaccess"
find "$ROOT" -name '.htaccess' \
  -not -path "$PUBLIC/.htaccess" \
  -not -path '*/vendor/*' \
  -not -path '*/.build-tmp/*' \
  -delete 2>/dev/null || true

if [ -L /var/www/html/tv ]; then
  rm -f /var/www/html/tv
  echo "Eliminado symlink /var/www/html/tv"
fi

echo "Listo: .htaccess canónico en public/. El panel y la cartelera son PHP/ES5."
