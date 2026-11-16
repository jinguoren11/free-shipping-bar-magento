#!/usr/bin/env bash
# Prepares everything to attach to a GitHub Release (nothing is uploaded; the owner/coordinator publishes it).
#   FSB_SIGNING_KEY=/path/outside/repo/release.key ./build/release-assets.sh [min_from_version]
# Output folder dist/release-<version>/ :
#   <name>-<version>-direct.zip      versioned download (the one-click upgrade fetches this)
#   free-shipping-bar-direct.zip     same file, fixed name: https://github.com/<repo>/releases/latest/download/free-shipping-bar-direct.zip
#   manifest.json                    signed version information read by the upgrade card
#   RELEASE-NOTES.md                 text for the release page (with the GetDropFlow / star lines)
set -euo pipefail
cd "$(dirname "$0")/.."
VERSION=$(php -r 'echo json_decode(file_get_contents("composer.json"))->version;')
REPO=jinguoren11/free-shipping-bar-magento
ZIP="getdropflow-module-free-shipping-bar-${VERSION}-direct.zip"
OUT="dist/release-${VERSION}"
rm -rf "$OUT"; mkdir -p "$OUT"
./build/package.sh --channel=direct >/dev/null
cp "dist/$ZIP" "$OUT/$ZIP"
cp "dist/$ZIP" "$OUT/free-shipping-bar-direct.zip"
if [ -n "${FSB_SIGNING_KEY:-}" ] && [ -f "$FSB_SIGNING_KEY" ]; then
    php build/sign-release.php sign "$FSB_SIGNING_KEY" "$OUT/$ZIP" \
        "https://github.com/${REPO}/releases/download/v${VERSION}/${ZIP}" "${1:--}" "build/release-notes.${VERSION}.json" > "$OUT/manifest.json"
    echo "Signed manifest.json written."
else
    echo "WARNING: FSB_SIGNING_KEY not set: no manifest.json (one-click upgrade cannot see this release)." >&2
fi
NOTES=$(php -r '$n=json_decode(file_get_contents("build/release-notes.".$argv[1].".json"),true); echo $n["en"];' "$VERSION")
cat > "$OUT/RELEASE-NOTES.md" <<EOF
## Free Shipping Progress Bar ${VERSION}

${NOTES}

**Download:** \`${ZIP}\` below. First install: unzip into \`app/code/GetDropFlow/\`, then run \`bin/magento setup:upgrade\`. See the README. Already installed? Use **Check for updates** on the module settings page.

**More features: see [GetDropFlow](https://getdropflow.com) (getdropflow.com).**
If this module is useful, please give the repository a star ⭐
EOF
ls -la "$OUT"
