---
title: "Free Shipping Progress Bar — User Guide"
subtitle: "Extension for Magento Open Source and Adobe Commerce 2.4.x"
---

# 1. What it does

Free Shipping Progress Bar shows your customers how close they are to free shipping, for example **"Add $12.50 more to get free shipping!"**, with a progress bar. When the goal is reached it shows a confirmation message.

It appears in three places, and each one can be switched off separately:

- the **mini cart** (the small cart that opens from the header),
- the **shopping cart page**,
- the **product page**.

![Mini cart, cart page and product page with the bar](images/01-overview.png)

**Features**

- Reads the free shipping amount from Magento's own *Free Shipping* delivery method, so you do not enter it twice. You can also enter your own amount.
- Updates immediately when a customer adds, removes or changes items or applies a coupon. No page reload.
- Works with the full page cache and Varnish.
- Uses the customer's display currency and the language of the store view. English, Spanish and Chinese texts are included; other languages fall back to English. You can write your own texts for every store view.
- Hidden automatically when the cart contains only virtual products, which have no shipping.
- Read-only: it only reads the cart total. It does not change orders, prices, payments or shipping rates, makes no requests to other websites, and stores no customer data.

# 2. Requirements and installation

- Magento Open Source or Adobe Commerce 2.4.x, PHP 8.1 or newer.

Install with Composer from your Magento root folder:

```
composer require getdropflow/module-free-shipping-bar
bin/magento module:enable GetDropFlow_FreeShippingBar
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

If your store is in production mode, run all the commands above. In developer mode `setup:di:compile` is not needed.

## Updating

Updates are delivered through Adobe Commerce Marketplace, the same way as the installation (Composer). Run `composer update getdropflow/module-free-shipping-bar`, then `bin/magento setup:upgrade`, `bin/magento setup:di:compile`, `bin/magento setup:static-content:deploy -f` and `bin/magento cache:flush`. **The extension never downloads or replaces code by itself and never contacts other websites.** Your settings are kept.

To uninstall: `bin/magento module:disable GetDropFlow_FreeShippingBar`, then `composer remove getdropflow/module-free-shipping-bar`, then `bin/magento setup:upgrade` and `bin/magento cache:flush`. The module creates no database tables.

## Themes

The bar works with Magento's standard themes (Luma, Blank) and themes built on them. All style classes start with `gdf-fsb-`; text color and font are inherited from your theme, and the layout adapts to narrow screens. If your theme shows the bar in the wrong place or not at all, set a **custom position** (section 4, *Theme Compatibility*). For **Hyvä** themes, install the separate compatibility package `getdropflow/module-free-shipping-bar-hyva` as well.

# 3. Quick start (2 minutes)

1. Go to **Stores > Configuration > Sales > Delivery Methods > Free Shipping**. Set **Enabled** to *Yes* and enter a **Minimum Order Amount** greater than 0, for example 50. Save.

   ![Free Shipping delivery method settings](images/02-free-shipping-method.png)

2. Go to **Stores > Configuration > Sales > Free Shipping Progress Bar**. Make sure **Enable** is *Yes*. Save.

   ![Module settings page](images/03-settings.png)

3. Open the cache management page (**System > Cache Management**) and flush the cache.
4. Add a product to the cart on your store. The bar appears in the mini cart, on the cart page and on product pages.

# 4. Settings reference

Path: **Stores > Configuration > Sales > Free Shipping Progress Bar**. Most settings can be set per website and per store view (use the scope switcher at the top left).

## General

| Setting | What it does |
|---|---|
| Enable | Turns the whole module on or off. |
| Show in Mini Cart | Shows the bar in the mini cart. |
| Show on Cart Page | Shows the bar at the top of the cart page. |
| Show on Product Page | Shows the bar on product pages, below the price. |

## Free Shipping Threshold

| Setting | What it does |
|---|---|
| Threshold Source | **Magento Free Shipping method (automatic)** uses the Minimum Order Amount of the Free Shipping delivery method. **Custom amount** uses the amount below. |
| Custom Threshold | Only for *Custom amount*. Enter the amount in your store's **base currency**. Customers see it converted to their display currency. |

With the automatic source, the bar is hidden when the Free Shipping method is disabled or its Minimum Order Amount is empty or 0.

## Messages

Leave a field empty to use the built-in text, which is translated automatically into the store view language. To write your own text, fill the field (you can do this per store view). You can use these placeholders:

- `{{amount}}` — the amount the customer still needs to add.
- `{{threshold}}` — the free shipping amount.

| Setting | Built-in text (English) |
|---|---|
| Message: Not Yet Free Shipping | Add $12.50 more to get free shipping! |
| Message: Free Shipping Reached | Congratulations! You have free shipping. |
| Message: Empty Cart | Free shipping on orders over $50.00 |

Example custom text: `Only {{amount}} left for free shipping on orders over {{threshold}}`

![Custom message example](images/04-custom-message.png)

## Appearance

| Setting | What it does |
|---|---|
| Progress Bar Color | A hex color, for example `#3b6ea5`. Invalid values are ignored and the default color is used. |
| Show Progress Bar | Show or hide the bar and keep only the text. |
| Show Percentage | Shows the percentage under the bar. |

## Theme Compatibility

Only needed if the bar does not appear where you want it with your theme.

| Setting | What it does |
|---|---|
| Cart Page / Product Page / Mini Cart: Custom Position (CSS selector) | A CSS selector of an element on the page, for example `.page-title-wrapper` or `#maincontent`. The bar is placed next to that element. Leave empty to use the standard position. If the element cannot be found on the page, the bar is not shown there. |
| Placement Relative to the Element | After the element, before the element, or inside it at the start or at the end. |

## About

| Setting | What it does |
|---|---|
| Show "More tools" Panel | **Off by default.** Turn it on to show a small fixed list of the author's other tools at the bottom of this settings page. It makes no network requests and collects no data. |

# 5. How the amount is calculated

- The bar compares the **cart subtotal after discounts** with the threshold. This is the same basis Magento uses for its Free Shipping method.
- A cart with a total of exactly the threshold counts as reached.
- With a coupon that lowers the cart below the threshold, the bar goes back to "add more".
- Shipping costs are not part of the amount.
- The bar is a hint for customers. The real decision about which shipping methods are offered is always made by Magento at checkout.

**Not read by the bar:** the Free Shipping method's country restrictions, customer groups, and free-shipping options of cart price rules or individual products. If your free shipping depends on those, the bar can show a message that does not match the checkout. In that case switch off the bar or use a custom amount that fits your rules.

# 6. Customer experience — states

| Cart state | What the customer sees |
|---|---|
| Empty cart | "Free shipping on orders over $50.00" (no progress bar) |
| Below the threshold | "Add $12.50 more to get free shipping!" and a progress bar |
| Threshold reached | "Congratulations! You have free shipping." and a full bar |
| Only virtual products | Nothing is shown |
| Module disabled / Free Shipping method disabled | Nothing is shown |

![Bar states: empty, in progress, reached](images/05-states.png)

# 7. Troubleshooting

| Problem | What to check |
|---|---|
| The bar does not appear anywhere | 1) **Enable** is *Yes* for the current store view. 2) With the automatic source, the Free Shipping method is enabled and its Minimum Order Amount is above 0 (for the right website). 3) Flush the cache and, in production mode, redeploy static content (`bin/magento setup:static-content:deploy -f`). 4) The cart does not contain only virtual products. |
| The bar appears in the cart but not in the mini cart | The theme must use the standard Magento mini cart. If your theme replaces it, use **Mini Cart: Custom Position** (section 4, Theme Compatibility). |
| The bar appears on the cart page but not on the product page | Check **Show on Product Page**. Custom product page layouts must keep the standard `product.info.main` container. |
| The text is in the wrong language | Texts follow the store view language. If a text is empty in the settings it uses the built-in translation; a custom text typed in one store view is shown only in that store view. After changing store view in the browser, reload the page once. |
| The amount is in the wrong currency | The custom threshold is entered in the **base currency** and converted to the display currency. Check the currency rates under **Stores > Currency Rates**. |
| The amount does not change after changing the cart | Clear the browser cache, and check the browser console (F12) for errors that mention `free-shipping-bar` or `bar.js`; another extension or a theme customization may be blocking the cart data from loading. |
| The bar says "free shipping" but checkout does not offer it | See "Not read by the bar" in section 5. |
| Settings page shows no "More tools" box | It is **off by default**; switch it on with **Show "More tools" Panel**. Also check that you are not looking at a website/store-view scope (the box is shown at the default scope). |

If you report a problem, please include your Magento version, PHP version, theme name, and any error text from the browser console (press F12) and from `var/log/system.log`.

# 8. Support, license and author

- License: MIT. The source code is open.
- Author: GetDropFlow — [getdropflow.com](https://getdropflow.com)
- Please include your Magento and PHP versions when contacting support.
