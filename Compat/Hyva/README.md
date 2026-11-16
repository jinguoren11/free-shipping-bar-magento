# Free Shipping Progress Bar — Hyvä compatibility module

Install this **in addition to** `getdropflow/module-free-shipping-bar` when your store uses a **Hyvä** theme (Alpine.js, no Knockout / RequireJS).
It shows the bar on the cart page and product page; on the mini cart (Hyvä drawer) it appears at a custom position you choose in the module settings (Theme Compatibility).
On Luma / Blank and other non-Hyvä themes it does nothing, so it is safe to leave installed.

```
composer require getdropflow/module-free-shipping-bar-hyva
bin/magento module:enable GetDropFlow_FreeShippingBarHyva
bin/magento setup:upgrade
bin/magento cache:flush
```
(Download edition: unzip into `app/code/GetDropFlow/FreeShippingBarHyva/`, then run the same commands.)

License: MIT.
