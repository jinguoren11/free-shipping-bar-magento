# Free Shipping Progress Bar for Magento 2

Shows customers how close they are to free shipping — in the mini cart, on the cart page and on product pages — with a progress bar.

- Reads the threshold from Magento's Free Shipping method (Minimum Order Amount), or use your own amount.
- Updates instantly when the cart changes; works with full page cache and Varnish.
- Uses the customer's currency and the store view language (English, Spanish and Chinese included).
- Read-only: it only reads the cart total. No tracking, no requests to other websites.

## Install and update
Install and update with Composer through Adobe Commerce Marketplace. The extension never downloads code by itself.

```
composer require getdropflow/module-free-shipping-bar
bin/magento module:enable GetDropFlow_FreeShippingBar
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

## Configure
Stores > Configuration > Sales > Free Shipping Progress Bar.

## License
MIT.
