#!/bin/sh
set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="$ROOT/src"
DIST="$ROOT/deploy/frontend-dist"

rm -rf "$DIST"
mkdir -p "$DIST/css" "$DIST/js" "$DIST/admin/css" "$DIST/admin/js"

cp "$SRC"/*.html "$DIST/"
cp "$SRC/css/"* "$DIST/css/"
cp "$SRC/js/"* "$DIST/js/"
cp "$SRC/admin/"*.html "$DIST/admin/"
cp "$SRC/admin/css/"* "$DIST/admin/css/"
cp "$SRC/admin/js/"* "$DIST/admin/js/"

API_URL="${BIBLIO_API_URL:-/api}"
ADMIN_API_URL="${BIBLIO_ADMIN_API_URL:-/api/admin}"

cat > "$DIST/js/env.js" <<EOF
window.__BIBLIO_API_URL__ = '$API_URL';
window.__BIBLIO_ADMIN_API_URL__ = '$ADMIN_API_URL';
EOF

echo "Frontend pret dans deploy/frontend-dist"
echo "API: $API_URL"
