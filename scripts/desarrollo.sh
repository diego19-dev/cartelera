#!/usr/bin/env bash
# =============================================================================
# Script de entorno de desarrollo local - Cartelera Digital SENA
# Inicia el servidor de desarrollo integrado de PHP con el router adecuado.
# Uso: bash scripts/desarrollo.sh [puerto]
# =============================================================================
Arranca la cartelera en local (ruta /tv/).
# Uso: bash scripts/desarrollo.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
HOST="${HOST:-127.0.0.1}"
PORT="${PORT:-8080}"
URL="http://${HOST}:${PORT}/tv/"
LOGIN_URL="http://${HOST}:${PORT}/tv/login.php"

if ss -tln 2>/dev/null | grep -q ":${PORT} "; then
  echo "El puerto ${PORT} ya está en uso. Prueba: PORT=8081 bash scripts/desarrollo.sh" >&2
  exit 1
fi

echo "Cartelera:  ${URL}"
echo "Panel:      ${LOGIN_URL}"
echo "Detener:    Ctrl+C"
echo

open_chrome() {
  sleep 1
  if command -v google-chrome >/dev/null 2>&1; then
    google-chrome --new-window "$URL" >/dev/null 2>&1 || true
  elif command -v google-chrome-stable >/dev/null 2>&1; then
    google-chrome-stable --new-window "$URL" >/dev/null 2>&1 || true
  elif command -v chromium >/dev/null 2>&1; then
    chromium --new-window "$URL" >/dev/null 2>&1 || true
  fi
}

open_chrome &

exec php -S "${HOST}:${PORT}" -t "$ROOT" "$ROOT/scripts/enrutador-desarrollo.php"
