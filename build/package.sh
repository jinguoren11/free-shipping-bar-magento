#!/usr/bin/env bash
# Builds the Marketplace upload zip: dist/getdropflow-module-free-shipping-bar-<version>.zip
# Contains only what Magento needs (no tests, docs, build files, .git). Run from anywhere.
set -euo pipefail
cd "$(dirname "$0")/.."

VERSION=$(php -r 'echo json_decode(file_get_contents("composer.json"))->version;')
OUT="dist/getdropflow-module-free-shipping-bar-${VERSION}.zip"

# Rule from Marketplace technical review: no TODO / FIXME comments in the package.
if grep -rInE '(^|[^A-Za-z])(TODO|FIXME)([^A-Za-z]|$)' --include='*.php' --include='*.xml' --include='*.js' --include='*.html' \
    --include='*.phtml' --include='*.css' --include='*.less' --include='*.csv' --include='*.json' \
    Block CustomerData Model etc view i18n registration.php composer.json; then
    echo "ERROR: TODO/FIXME found (see above). Marketplace will reject the package." >&2
    exit 1
fi

for f in $(find . -name '*.php' -not -path './vendor/*' -not -path './.git/*' -not -path './dist/*'); do php -l "$f" >/dev/null; done
php -r 'json_decode(file_get_contents("composer.json"), false, 512, JSON_THROW_ON_ERROR);'

mkdir -p dist && rm -f "$OUT"
zip -qr "$OUT" Block CustomerData Model etc view i18n registration.php composer.json LICENSE README.md \
    -x '*.DS_Store'
SIZE=$(wc -c < "$OUT")
echo "Built $OUT ($SIZE bytes; Marketplace limit is 30 MB)"
