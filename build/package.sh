#!/usr/bin/env bash
# Builds an upload/download zip from ONE source tree.
#   ./build/package.sh --channel=marketplace   Adobe Commerce Marketplace edition (no code that downloads code,
#                                               "More tools" panel off by default). Files at the zip root (Composer package).
#   ./build/package.sh --channel=direct        Edition for getdropflow.com / GitHub (one-click upgrade). Zip holds one folder
#                                               "FreeShippingBar/" to unzip into app/code/GetDropFlow/. (default)
# Output: dist/getdropflow-module-free-shipping-bar-<version>-<channel>.zip
set -euo pipefail
cd "$(dirname "$0")/.."

CHANNEL=direct
for arg in "$@"; do
    case "$arg" in
        --channel=marketplace) CHANNEL=marketplace ;;
        --channel=direct) CHANNEL=direct ;;
        *) echo "Unknown option: $arg" >&2; exit 2 ;;
    esac
done

VERSION=$(php -r 'echo json_decode(file_get_contents("composer.json"))->version;')
OUT="dist/getdropflow-module-free-shipping-bar-${VERSION}-${CHANNEL}.zip"
STAGE="dist/stage-${CHANNEL}"
FILES=(Block CustomerData Model etc view i18n registration.php composer.json LICENSE README.md)

rm -rf "$STAGE"; mkdir -p "$STAGE/FreeShippingBar"
for f in "${FILES[@]}"; do cp -R "$f" "$STAGE/FreeShippingBar/"; done
find "$STAGE" -name '.DS_Store' -delete
MOD="$STAGE/FreeShippingBar"

if [ "$CHANNEL" = marketplace ]; then
    # 0) promotion-free README for the Marketplace edition
    cp README.marketplace.md "$MOD/README.md"
    # 1) remove direct-only files and blocks
    rm -rf "$MOD/Model/Upgrade"
    python3 -I - "$MOD" <<'PY'
import sys, re, pathlib
root = pathlib.Path(sys.argv[1])
pat = re.compile(r'[ \t]*<!--\s*CHANNEL:direct-begin\s*-->.*?<!--\s*CHANNEL:direct-end\s*-->[ \t]*\n?', re.S)
for p in list(root.rglob('*.xml')) + list(root.rglob('*.phtml')):
    t = p.read_text(encoding='utf-8')
    n = pat.sub('', t)
    if n != t:
        p.write_text(n, encoding='utf-8')
# direct-only PHP/JS/templates are listed here (none yet) and removed:
for rel in ['Block/Adminhtml/System/Config/UpgradeCard.php',
            'Block/Adminhtml/System/Config/StarLink.php',
            'view/adminhtml/templates/upgrade-card.phtml',
            'view/adminhtml/web/js/upgrade-card.js']:
    f = root / rel
    if f.exists():
        f.unlink()
PY
    # 2) edition settings
    sed -i "s#const CURRENT = 'direct'; // CHANNEL-VALUE#const CURRENT = 'marketplace'; // CHANNEL-VALUE#" "$MOD/Model/Channel.php"
    sed -i 's#<show_more_tools>1</show_more_tools>#<show_more_tools>0</show_more_tools>#' "$MOD/etc/config.xml"
    # 3) the Marketplace edition must not contain anything that downloads, unpacks or runs code
    if grep -rInE '(curl_|shell_exec|proc_open|popen|passthru|\bexec\s*\(|\bsystem\s*\(|ZipArchive|sodium_crypto_sign|api\.github\.com|releases/download|Model\\\\Upgrade|Upgrade\\\\)' \
        --include='*.php' --include='*.xml' --include='*.js' --include='*.phtml' --include='*.html' "$MOD"; then
        echo "ERROR: direct-only code found in the Marketplace edition (see above)." >&2
        exit 1
    fi
fi

# Marketplace technical review: no TODO / FIXME comments in the package.
if grep -rInE '(^|[^A-Za-z])(TODO|FIXME)([^A-Za-z]|$)' --include='*.php' --include='*.xml' --include='*.js' --include='*.html' \
    --include='*.phtml' --include='*.css' --include='*.less' --include='*.csv' --include='*.json' "$MOD"; then
    echo "ERROR: TODO/FIXME found (see above). Marketplace will reject the package." >&2
    exit 1
fi
while IFS= read -r f; do php -l "$f" >/dev/null; done < <(find "$MOD" -name '*.php')
php -r 'json_decode(file_get_contents($argv[1]), false, 512, JSON_THROW_ON_ERROR);' "$MOD/composer.json"

mkdir -p dist && rm -f "$OUT"
if [ "$CHANNEL" = marketplace ]; then
    (cd "$MOD" && zip -qr "../../../$OUT" .)
else
    (cd "$STAGE" && zip -qr "../../$OUT" FreeShippingBar)
fi
rm -rf "$STAGE"
SIZE=$(wc -c < "$OUT")
echo "Built $OUT ($SIZE bytes; Marketplace limit is 30 MB) [channel=$CHANNEL]"
