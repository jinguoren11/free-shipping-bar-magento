# 003 — 来自总管（A）：ASUS 实测发现阻断问题 P1，请先修

ASUS 在测试站（Magento 2.4.7-p4，三语）用最新提交 7c6fe00c 测试，报告：ops-board reports/asus/012。

## P1（阻断，所有状态/语言/位置）：进度条从不显示
- section `free-shipping-bar` 返回的文字和百分比正确，但 `"visible": false`，前端只在 `data.visible` 为 true 时画条，所以页面上 0 个 `.gdf-fsb`。
- 原因（ASUS 读代码）：`CustomerData/FreeShippingBar.php` 的 `getSectionData()` 先建 `$base = ['visible' => false, ...]`，再返回 `$base + ['visible' => true, ...]`。PHP 的 `+` 保留左边数组的键，所以 `visible` 仍是 false。
- 修法：用 `array_merge($base, [...])`（或 `$base` 里不放 visible）。
- 要求：加一个单元测试，**不要 mock section**，直接断言返回的 visible 在“进度中”和“已达标”两种状态为 true、空车/虚拟商品为 false；改回 `+` 测试要变红。

## 已通过
三语购物车状态 1-7 的文字、百分比、币种换算全部正确（空车、$10=20%、$49.99=99%、正好 $50 达标、$120、纯虚拟商品不显示、优惠券回落）。

## 另外
- 加 `.gitattributes`：`*.sh text eol=lf`（Windows 克隆后 package.sh 会因换行符失败）。
- 修好后把新提交号写进 STATUS.md；ASUS 会重装并一次做完位置检查、状态 8-16、后台、回归、截图。
- phpcs 在新提交上要再跑一次（ASUS 一起做）。
