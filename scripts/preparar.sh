#!/usr/bin/env bash
# =============================================================================
# Script de preparación inicial y verificación de dependencias
# Configura permisos de carpetas de almacenamiento y valida requisitos de PHP.
# Uso: bash scripts/preparar.sh
# =============================================================================
Prepara directorios de escritura y deja un solo .htaccess.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"

mkdir -p "$ROOT/img/uploads"/{academic,center,general,branding,welcome,gallery,media}
mkdir -p "$ROOT/logs/cache" "$ROOT/logs/backups" "$ROOT/css" "$ROOT/js" "$ROOT/lib"

if [ ! -f "$ROOT/src/api.php" ]; then
  echo "ERROR: falta src/api.php" >&2
  exit 1
fi
if [ ! -f "$ROOT/src/cartelera.html" ]; then
  echo "ERROR: falta src/cartelera.html" >&2
  exit 1
fi
if [ ! -f "$ROOT/.htaccess" ] || [ ! -f "$ROOT/index.php" ]; then
  echo "ERROR: faltan .htaccess o index.php en la raíz" >&2
  exit 1
fi

find "$ROOT" -name '.htaccess' \
  -not -path "$ROOT/.htaccess" \
  -not -path '*/lib/*' \
  -not -path '*/.build-tmp/*' \
  -delete 2>/dev/null || true

echo "Listo: raíz limpia + src/ en español; carpetas de escritura OK."
