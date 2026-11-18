# Upgrading the download edition (GitHub)

(Separate from the Marketplace user guide on purpose: the Marketplace edition contains no upgrade code and its documents do not mention it.)

If you installed the module from the GitHub download (folder `app/code/GetDropFlow/FreeShippingBar`), the settings page has an **Updates** card at the top (Stores > Configuration > Sales > Free Shipping Progress Bar).
1. Press **Check for updates**. This is the only moment the module contacts the internet: it reads the public release information on GitHub (a plain request that sends nothing about your shop). The result is remembered for 6 hours; you can check again after 10 minutes.
2. If a newer version exists you see what is new. Press **Upgrade now (one click)** and confirm.
3. Keep the page open. The card shows the steps (in the language of your admin): download and check the file (size, checksum and the author's signature), back up the current version, put the shop into maintenance mode for a few minutes, install, check the result, leave maintenance mode.
4. If anything fails, your previous version is put back automatically and the card says so. If even that fails the shop stays in maintenance mode and the card shows the restore steps (rename the folder `var/getdropflow_fsb_upgrade/previous-FreeShippingBar` back to `app/code/GetDropFlow/FreeShippingBar`, then `bin/magento setup:upgrade`, `setup:di:compile`, `setup:static-content:deploy -f`, `cache:flush`, `maintenance:disable`).
   - While the shop is in maintenance mode (a few minutes) the admin cannot answer, so the card shows "the shop is in maintenance mode ... it will continue by itself" and waits; this is normal.
   - If the card says the upgrade stopped "before anything on your shop was changed" (for example a damaged download, a wrong checksum, no disk space), your shop was never touched and nothing was rebuilt. The card names the reason.
   - If the process does not start within 30 seconds, the card says so and nothing is changed. Typical cause: the server does not allow background commands, or has no PHP program with the same version as the website.
5. **If an upgrade was killed half way** (power cut, server restart, process killed) the shop can stay in maintenance mode and the admin is then unreachable. The module puts the old version back by itself: every minute Magento's cron looks for a dead upgrade (its process is gone), restores the old files, leaves maintenance mode and removes the leftovers (`var/getdropflow_fsb_upgrade/new`, `package.zip`). You can do the same at once on the server:
   ```
   php bin/magento getdropflow:fsb:upgrade --recover
   ```
   It also retries after "the restore did not finish". Use the same PHP version as your website (for example `php8.3 bin/magento ...`). If the shop was already in maintenance mode before you started, it stays so.
6. If the card says one-click upgrade is not possible on your server (for example: installed with Composer, folder not writable, commands not allowed), it tells you why. Then upgrade by hand: download the new `free-shipping-bar-direct.zip`, replace the folder, and run the same commands as above. Your settings are kept.
The first installation is always by hand; one-click upgrade works from the next version on. The Hyvä compatibility package is upgraded by hand (replace its folder and run the same commands).

