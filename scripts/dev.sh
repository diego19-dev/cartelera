#!/usr/bin/env bash
# Arranca la cartelera en local y la abre en Chrome.
# Uso: bash scripts/dev.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
HOST="${HOST:-127.0.0.1}"
PORT="${PORT:-8080}"
DOC="$ROOT/.dev-docroot"
URL="http://${HOST}:${PORT}/tv/"
LOGIN_URL="http://${HOST}:${PORT}/tv/login.php"

mkdir -p "$DOC"
ln -sfn "$ROOT/public" "$DOC/tv"

if ss -tln 2>/dev/null | grep -q ":${PORT} "; then
  echo "El puerto ${PORT} ya está en uso. Prueba: PORT=8081 bash scripts/dev.sh" >&2
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

exec php -S "${HOST}:${PORT}" -t "$DOC" "$ROOT/scripts/dev-router.php"
