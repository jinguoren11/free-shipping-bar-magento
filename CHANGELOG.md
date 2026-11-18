# Changelog

## 1.0.0
- Download edition, one-click upgrade fixes after the first real-server test: the upgrade now really starts (the background command was refused because the page had already marked it "running"); it uses the same PHP version as the website and reports "did not start within 30 seconds" instead of hanging; a failure before anything was changed (bad checksum, damaged download) no longer rebuilds the shop or says "restored"; every failure reason and every step name is shown in English, Spanish and Chinese; the card waits quietly while the shop is in maintenance mode; a killed upgrade is restored by cron every 5 minutes or with `bin/magento getdropflow:fsb:upgrade --recover`, and its leftovers are removed; the engine uses Magento's Shell service and fewer direct PHP file calls (phpcs).
- First release: free shipping progress bar in the mini cart, cart page and product page; threshold read from Magento's Free Shipping method or set by hand; English, Spanish and Chinese texts.
- Download edition: one-click upgrade from the settings page (check, back up, install, automatic restore on failure). The Marketplace edition contains none of this code.
- Breeze (Swissup) support for the mini cart; the bar no longer shows invisible in a mini cart whose container has `font-size: 0`.
