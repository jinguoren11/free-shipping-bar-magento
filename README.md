# Free Shipping Progress Bar for Magento 2

Show customers how close they are to **free shipping** — in the mini cart, on the cart page and on product pages.

> "Add $12.50 more to get free shipping!" + a progress bar. Free, open source (MIT).

## Features
- Reads the threshold from Magento's own **Free Shipping** method (Minimum Order Amount) — no need to enter it twice. Or override it in the module settings.
- Mini cart, cart page and product page, each can be switched off separately.
- Updates instantly when the cart changes, no page reload.
- Works with Full Page Cache and Varnish (data is loaded in the browser).
- Multi-currency and multi-store-view aware; texts are translated (English, Spanish, Chinese included) and can be overridden per store view.
- Read-only: never touches orders, payments, prices or shipping calculation. No tracking, no external requests.

## Download
- **Main download (recommended): https://getdropflow.com/downloads/free-shipping-bar/** — the edition with one-click upgrade.
- Adobe Commerce Marketplace edition: install with Composer (updates come from Adobe).
- Source code: this repository (MIT).

## Install
```
composer require getdropflow/module-free-shipping-bar
bin/magento module:enable GetDropFlow_FreeShippingBar
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Upgrading
- **Website / GitHub edition**: Stores > Configuration > Sales > Free Shipping Progress Bar > "Check for updates" > one click. It backs up the old version, upgrades, and puts everything back automatically if anything goes wrong. It only contacts getdropflow.com when you press "Check for updates", sends no store data, and has no background jobs. (Coming in the next release; the first install is manual.)
- **Marketplace edition**: updates come through Adobe Commerce; the extension never downloads code by itself.

## Configure
Stores > Configuration > Sales > **Free Shipping Progress Bar**.
For the automatic threshold, enable Stores > Configuration > Sales > Delivery Methods > **Free Shipping** and set a Minimum Order Amount.

## Compatibility
Magento Open Source / Adobe Commerce 2.4.x, PHP 8.1+.

## Tests
Unit tests need only PHPUnit 10 (no Magento): `FSB_AUTOLOAD=/path/to/vendor/autoload.php phpunit`.

## License
MIT. Made by [GetDropFlow](https://getdropflow.com).
