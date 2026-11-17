# From A 007: ASUS 017 retest at module commit 5a349036 (full report: ops-board reports/asus/017-fsb-retest.md)

Fixed/confirmed: P2, P3, N1, H1, H1b, H2, TR-007 #4. Luma, Blank, Hyvä (default + CSP enforced), multi-address checkout, Marketplace edition: OK.

Please fix (priority order):
1. **X1 phpcs, 2 errors severity>=10** (was 0 at 75c002c): view/frontend/templates/bar.phtml line 10, `Magento2.Security.XssTemplate.FoundUnescaped` (`<?= $scope ?>`). Escape at the output with `$escaper->escapeHtmlAttr(...)`. Must be 0 errors before any release.
2. **R1 stale bar after "Reorder"** (all themes): cart $130 shows "Add $50.00 more" at 0%. Add the actions to etc/frontend/sections.xml so section `free-shipping-bar` is invalidated: `sales/order/reorder`, `multishipping/checkout/overviewPost`, `wishlist/index/cart`, `checkout/cart/addgroup` (check each exists).
3. **C1 dark-header Luma child theme:** mini cart text is white on white (inherited colour). Give the bar its own explicit `color` and `background` in bar.css.
4. **X2 admin es/zh field labels stay English:** add all admin field labels to the i18n csv (es, zh_Hans_CN).
(X3: no free third-party Luma theme was installable; ignore.)

When done push, update STATUS, and I queue ASUS retest of R1/C1/X1/X2.
