#!/usr/bin/env bash
# Builds mm-chile-2026.zip with the folder name WordPress expects.
# GitHub's "Download ZIP" would unpack as chile-2026-main/, which WordPress
# treats as a different plugin — install this ZIP instead.
set -euo pipefail

cd "$(dirname "$0")"
SLUG="mm-chile-2026"
VERSION="$(sed -n 's/^ \* Version: *//p' mm-chile-2026.php | tr -d '[:space:]')"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

mkdir -p "$TMP/$SLUG"
cp mm-chile-2026.php README.md "$TMP/$SLUG/"
cp -R includes assets "$TMP/$SLUG/"

for f in "$TMP/$SLUG"/includes/*.php "$TMP/$SLUG"/mm-chile-2026.php; do
	php -l "$f" >/dev/null
done

rm -f "$SLUG.zip"
(cd "$TMP" && zip -qr "$OLDPWD/$SLUG.zip" "$SLUG")
echo "Built $SLUG.zip (version $VERSION)"
