# Free Shipping Progress Bar for Magento 2

Show customers how close they are to **free shipping** — in the mini cart, on the cart page and on product pages.

> "Add $12.50 more to get free shipping!" + a progress bar. Free, open source (MIT).

**More features: see [GetDropFlow](https://getdropflow.com) (getdropflow.com).**
If this module is useful to you, please give the repository a star ⭐ — it helps others find it.

## Features
- Reads the threshold from Magento's own **Free Shipping** method (Minimum Order Amount) — no need to enter it twice. Or override it in the module settings.
- Mini cart, cart page and product page, each can be switched off separately.
- Updates instantly when the cart changes, no page reload.
- Works with Full Page Cache and Varnish (data is loaded in the browser).
- Multi-currency and multi-store-view aware; texts are translated (English, Spanish, Chinese included) and can be overridden per store view.
- Read-only: never touches orders, payments, prices or shipping calculation. No tracking.

## Download
- **Main download: the [Releases page of this repository](https://github.com/jinguoren11/free-shipping-bar-magento/releases)** — every version has a zip attached (`getdropflow-module-free-shipping-bar-<version>-direct.zip`). This edition has one-click upgrade.
- Adobe Commerce Marketplace edition: install with Composer (updates come from Adobe).

## Install (first time, manual)
1. Download the zip from Releases and unzip it into your Magento folder `app/code/GetDropFlow/` (you get `app/code/GetDropFlow/FreeShippingBar/`).
2. Run:
```
bin/magento module:enable GetDropFlow_FreeShippingBar
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Upgrading
- **Download edition**: Stores > Configuration > Sales > Free Shipping Progress Bar > press **Check for updates**, then one click. It backs up the old version, upgrades, and puts everything back automatically if anything goes wrong.
  It reads the public latest-release information of this GitHub repository (result cached), only when you press the button; it sends no store data and has no background jobs. *(One-click upgrade is being finished; until it is released, upgrade by repeating the manual install with the new zip.)*
- **Marketplace edition**: updates come through Adobe Commerce; the module never downloads code by itself.

## Configure
Stores > Configuration > Sales > **Free Shipping Progress Bar**.
For the automatic threshold, enable Stores > Configuration > Sales > Delivery Methods > **Free Shipping** and set a Minimum Order Amount.

## Compatibility
Magento Open Source / Adobe Commerce 2.4.x, PHP 8.1+.

## Tests
Unit tests need only PHPUnit 10 (no Magento): `FSB_AUTOLOAD=/path/to/vendor/autoload.php phpunit`.

## License
MIT. Made by [GetDropFlow](https://getdropflow.com).
