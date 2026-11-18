# Upgrading the download edition (GitHub)

(Separate from the Marketplace user guide on purpose: the Marketplace edition contains no upgrade code and its documents do not mention it.)

If you installed the module from the GitHub download (folder `app/code/GetDropFlow/FreeShippingBar`), the settings page has an **Updates** card at the top (Stores > Configuration > Sales > Free Shipping Progress Bar).
1. Press **Check for updates**. This is the only moment the module contacts the internet: it reads the public release information on GitHub (a plain request that sends nothing about your shop). The result is remembered for 6 hours; you can check again after 10 minutes.
2. If a newer version exists you see what is new. Press **Upgrade now (one click)** and confirm.
3. Keep the page open. The card shows the steps: download and check the file (size, checksum and the author's signature), back up the current version, put the shop into maintenance mode for a few minutes, install, check the result, leave maintenance mode.
4. If anything fails, your previous version is put back automatically and the card says so. If even that fails the shop stays in maintenance mode and the card shows the restore steps (rename the folder `var/getdropflow_fsb_upgrade/previous-FreeShippingBar` back to `app/code/GetDropFlow/FreeShippingBar`, then `bin/magento setup:upgrade`, `setup:di:compile`, `setup:static-content:deploy -f`, `cache:flush`, `maintenance:disable`).
5. If the card says one-click upgrade is not possible on your server (for example: installed with Composer, folder not writable, commands not allowed), it tells you why. Then upgrade by hand: download the new `free-shipping-bar-direct.zip`, replace the folder, and run the same commands as above. Your settings are kept.
The first installation is always by hand; one-click upgrade works from the next version on. The Hyvä compatibility package is upgraded by hand (replace its folder and run the same commands).

