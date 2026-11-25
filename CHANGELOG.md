# Changelog

## 1.1.0
- Download edition: **authorize the store once with a free GetDropFlow account** ("Log in to GetDropFlow to authorize", no key to copy). Until the store is authorized the bar is not shown on the storefront and the settings page shows only the Authorization card (plus Updates and More tools). The cart, checkout and orders are never touched.
- An authorized store keeps working for 30 days when the website cannot be reached (29 days yes, 31 days no); only the website's clear "store removed / revoked" answer locks it at once. A paid plan that ended shows a blue note and never locks. The store keeps its own site token (no license key is stored); a cron job checks every 6 hours, the storefront never contacts the website. Privacy: only the store address and the account e-mail are shared.
- Advanced option: enter a license key by hand. "Check now" and "Disconnect this store" buttons.
- The Marketplace edition is unchanged: no authorization, no network code.
- A store upgraded from 1.0.0 must authorize once (no grace period: the plugin had no existing users).
- Guides in English, Spanish and Chinese: `docs/user-guide/AUTHORIZATION.*.md`.
- Hyva: a store that is not authorized no longer shows the bar to first-time visitors (the page's starting state now asks the same access gate as the section).
- After an order is placed the bar no longer shows the old "Add $X more" amount on the next page (the cart signature is compared and the bar data reloaded).
- Bar data is re-checked against the cart when they disagree about an empty cart (Luma after an order) and invalidated when an order is placed at checkout.
- Manual key box: the typed key travels as `license_key` (Magento's own `key` URL parameter no longer clashes).
- Download edition: the data builder itself honours the authorization, so an older Hyva compat module left by a one-click upgrade cannot show the bar on a locked store.
- The "paid plan ended" note shows the date only (no plan code).
- Manual key box (round 1): separate messages for "not shaped like a key" and "the website does not know this key" (English, Spanish, Chinese).

## 1.0.0
- Download edition, one-click upgrade fixes after the first real-server test: the upgrade now really starts (the background command was refused because the page had already marked it "running"); it uses the same PHP version as the website and reports "did not start within 30 seconds" instead of hanging; a failure before anything was changed (bad checksum, damaged download) no longer rebuilds the shop or says "restored"; every failure reason and every step name is shown in English, Spanish and Chinese; the card waits quietly while the shop is in maintenance mode; a killed upgrade is restored by cron every minute or with `bin/magento getdropflow:fsb:upgrade --recover`, and its leftovers are removed; the engine uses Magento's Shell service and fewer direct PHP file calls (phpcs).
- First release: free shipping progress bar in the mini cart, cart page and product page; threshold read from Magento's Free Shipping method or set by hand; English, Spanish and Chinese texts.
- Download edition: one-click upgrade from the settings page (check, back up, install, automatic restore on failure). The Marketplace edition contains none of this code.
- Breeze (Swissup) support for the mini cart; the bar no longer shows invisible in a mini cart whose container has `font-size: 0`.
