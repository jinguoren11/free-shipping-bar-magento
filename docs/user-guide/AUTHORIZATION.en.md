# Free Shipping Progress Bar: authorize your store (version 1.1.0)

From version 1.1.0 the download edition (GitHub / getdropflow.com) must be authorized once with a **free GetDropFlow account**. The bar costs nothing; the account only tells us which store uses it. Until the store is authorized, **the bar is not shown to your customers**. Your cart, checkout, orders, prices and shipping are never touched, locked or not.

## Authorize in 4 steps

1. Open **Stores > Configuration > Sales > Free Shipping Progress Bar**. While the store is not authorized you see only the **Authorization** card (and the Updates and More tools cards).
2. Press **Log in to GetDropFlow to authorize**. A new tab opens (if your browser blocks it, press **Open the authorization page again**). The settings page shows a code like `BCDF-GHJK`.
3. On the GetDropFlow page, log in (or create a free account), check that the code is the same as in your admin, and press **Allow**.
4. Go back to the admin: within a few seconds the card says **This store is authorized** and the settings of the bar appear. Nothing else to do; there is no key to copy.

## What is shared

Only the **web address of your store** and the **e-mail of the account** you log in with. The store keeps its own secret token (not your account key) and nothing else is read from your shop: no orders, no customers, no products. Details: the privacy page on getdropflow.com.

## Rules (what happens when)

- **New store, not authorized**: only the Authorization card on the settings page; the bar is hidden on the storefront. Cart, checkout and orders work as always.
- **Free membership is enough.** Nothing is paid. A paid GetDropFlow plan also works.
- **The website cannot be reached** (your server is offline, the website is down): an authorized store keeps working for **30 days** from the last successful check; the card shows the days left. The check repeats by itself every 6 hours. Only the website's clear answer "this store was removed / revoked" locks the store at once.
- **A paid plan ended**: the card shows a blue note. The free membership continues, the bar keeps working, nothing is locked.
- **Disconnect this store** (button on the card) forgets the credential on your store; the bar is hidden until you authorize again. You can also remove the store in your GetDropFlow account; the store is locked at the next check (up to 6 hours, or press **Check now**).
- **Upgrading from 1.0.0**: a store without authorization is locked after the upgrade (the bar disappears from the storefront) until you authorize it once. Do the 4 steps above after upgrading.
- **Marketplace edition** (Adobe Commerce Marketplace) does not need authorization: it cannot link to the website.
- **Advanced**: if you already have a GetDropFlow license key you can open **Advanced: enter a key by hand**, type it and press **Save and check**. The button above is the normal way.

## Common problems

| | |
|---|---|
| **The button does nothing / no new tab** | Your browser blocked the pop-up. Press **Open the authorization page again**, or allow pop-ups for your admin address. |
| **"The GetDropFlow website cannot be reached from this server right now"** | Your server cannot open https://license.getdropflow.com (firewall, DNS, outgoing connections blocked). Ask your hosting provider to allow outgoing https to that address, then try again. An already authorized store keeps working for 30 days. |
| **"The code expired"** | You took more than 10 minutes. Press the login button again; a new code is made. |
| **"You pressed Deny"** | Nothing was changed. Start again and press **Allow**. |
| **"Too many tries"** | Wait a few minutes. The website limits how often a store can ask. |
| **"The GetDropFlow website no longer knows this store"** | The store was removed in your account, or the account was closed. Log in again to authorize it. |
| **The bar is not shown but the card says "authorized"** | Reload the page; the bar data is loaded in the browser and cached. Clear the browser's local storage once (Magento keeps the old answer there) or open a private window. |
| **"The key was saved but the website could not confirm it yet"** | Press **Check now** in a minute. If it stays like this the key is not valid for this store address. |

## Screenshots (click an image to enlarge)

Images are added by the screenshot task (red box on the key spot).

<a href="../images/auth-01-locked-card.en.png"><img src="../images/auth-01-locked-card.en.png" alt="Settings page while the store is not authorized: only the Authorization card (plus Updates and More tools) is shown." width="360"></a><br><sub>1. Settings page while the store is not authorized: only the Authorization card (plus Updates and More tools) is shown.</sub>

<a href="../images/auth-02-code-waiting.en.png"><img src="../images/auth-02-code-waiting.en.png" alt="After pressing the button: the code and the "waiting" text." width="360"></a><br><sub>2. After pressing the button: the code and the "waiting" text.</sub>

<a href="../images/auth-03-website-allow.en.png"><img src="../images/auth-03-website-allow.en.png" alt="The GetDropFlow website page: what is shared (store address and account e-mail), the code, the Allow button." width="360"></a><br><sub>3. The GetDropFlow website page: what is shared (store address and account e-mail), the code, the Allow button.</sub>

<a href="../images/auth-04-authorized.en.png"><img src="../images/auth-04-authorized.en.png" alt="The card after authorization: "This store is authorized" and the membership." width="360"></a><br><sub>4. The card after authorization: "This store is authorized" and the membership.</sub>

<a href="../images/auth-05-offline.en.png"><img src="../images/auth-05-offline.en.png" alt="The card while the website cannot be reached: days left." width="360"></a><br><sub>5. The card while the website cannot be reached: days left.</sub>

<a href="../images/auth-06-lapsed.en.png"><img src="../images/auth-06-lapsed.en.png" alt="The blue note when a paid plan ended (the free bar keeps working)." width="360"></a><br><sub>6. The blue note when a paid plan ended (the free bar keeps working).</sub>

<a href="../images/auth-07-rejected.en.png"><img src="../images/auth-07-rejected.en.png" alt="The message when the website no longer knows the store." width="360"></a><br><sub>7. The message when the website no longer knows the store.</sub>

<a href="../images/auth-08-advanced-key.en.png"><img src="../images/auth-08-advanced-key.en.png" alt=""Advanced: enter a key by hand" opened." width="360"></a><br><sub>8. "Advanced: enter a key by hand" opened.</sub>

<a href="../images/auth-09-storefront-locked.en.png"><img src="../images/auth-09-storefront-locked.en.png" alt="Cart page of the shop while locked: no bar, cart and checkout work as usual." width="360"></a><br><sub>9. Cart page of the shop while locked: no bar, cart and checkout work as usual.</sub>

