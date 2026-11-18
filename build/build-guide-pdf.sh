#!/usr/bin/env bash
# Builds the English user guide PDF for Marketplace (needs pandoc + Chromium/Playwright).
# If any screenshot is missing, a grey placeholder is used and the file is named ...-DRAFT.pdf (never upload a DRAFT).
set -euo pipefail
cd "$(dirname "$0")/.."
SRC=docs/user-guide/USER-GUIDE.en.md
WORK=dist/guide-work
VERSION=$(php -r 'echo json_decode(file_get_contents("composer.json"))->version;')
rm -rf "$WORK"; mkdir -p "$WORK/images"
cp -r docs/user-guide/images/. "$WORK/images/" 2>/dev/null || true

DRAFT=0
for img in $(grep -oE 'images/[A-Za-z0-9_.-]+\.png' "$SRC" | sort -u); do
  if [ ! -s "docs/user-guide/$img" ]; then
    DRAFT=1
    python3 -I - "$WORK/$img" <<'PY'
import sys, zlib, struct
w, h = 900, 220
raw = b''.join(b'\x00' + b'\xee\xee\xee' * w for _ in range(h))
def chunk(t, d):
    c = struct.pack('>I', len(d)) + t + d
    return c + struct.pack('>I', zlib.crc32(t + d) & 0xffffffff)
png = b'\x89PNG\r\n\x1a\n' + chunk(b'IHDR', struct.pack('>IIBBBBB', w, h, 8, 2, 0, 0, 0)) + chunk(b'IDAT', zlib.compress(raw)) + chunk(b'IEND', b'')
open(sys.argv[1], 'wb').write(png)
PY
  fi
done

cat > "$WORK/style.css" <<'CSS'
body{font-family:Helvetica,Arial,sans-serif;font-size:11pt;line-height:1.45;color:#222;max-width:none}
h1{font-size:17pt;border-bottom:2px solid #3b6ea5;padding-bottom:3px;margin-top:22px;page-break-after:avoid}
h2{font-size:13pt;margin-top:16px;page-break-after:avoid}
table{border-collapse:collapse;width:100%;margin:8px 0;font-size:10pt}
th,td{border:1px solid #bbb;padding:5px 7px;vertical-align:top;text-align:left}
th{background:#eef2f7}
code,pre{font-family:Menlo,Consolas,monospace;font-size:9.5pt;background:#f3f3f3}
pre{padding:8px;white-space:pre-wrap}
img{max-width:100%;border:1px solid #ddd;margin:6px 0}
header h1.title{border:0;font-size:24pt}
CSS
pandoc "$SRC" -s --toc --toc-depth=1 --resource-path="$WORK" --css="$WORK/style.css" --embed-resources -o "$WORK/guide.html"

OUT="dist/Free-Shipping-Progress-Bar-User-Guide-${VERSION}.pdf"
[ "$DRAFT" = 1 ] && OUT="dist/Free-Shipping-Progress-Bar-User-Guide-${VERSION}-DRAFT.pdf"
NODE_PATH=/opt/node-tools/node_modules node -e "
const {chromium}=require('playwright');
(async()=>{const b=await chromium.launch();const p=await b.newPage();
await p.goto('file://'+process.argv[1]);
await p.pdf({path:process.argv[2],format:'A4',margin:{top:'18mm',bottom:'18mm',left:'16mm',right:'16mm'},printBackground:true});
await b.close();})();" "$PWD/$WORK/guide.html" "$PWD/$OUT"
echo "Built $OUT ($(wc -c < "$OUT") bytes; limit 5 MB). DRAFT=$DRAFT"
