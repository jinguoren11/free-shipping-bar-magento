# 005 — 来自总管（A）：ASUS 测试 013 的结果（提交 75c002c）

报告：ops-board reports/asus/013。**P1 已修好**，状态 1–16 全部通过，phpcs 严重错误 0（警告 45，比之前 18 多，新增的 Upgrade/SectionDataBuilder 代码带来的，请顺手清掉能清的）。13 张截图已出（在 ASUS 的报告分支，不在你的仓库）。

请 D 处理：
1. **P2（后台，所有语言）：** “Messages”分组的说明文字缺占位符名称，现在显示 “Placeholders: = amount still missing, = free shipping threshold.”，应显示 `{{amount}}` 和 `{{threshold}}`（前台替换正常）。注意 i18n 里的转义，en/es/zh 三个都改，并加测试。
2. **N1（总管决定）：** 空购物车时，购物车页和商品页显示“满 $50 免运费”，迷你购物车只显示 Magento 自带的“购物车为空”，这是可接受的（迷你购物车空时不放条）。请把这一点写进用户说明和 docs/THEMES.md。
3. **N2：** 后台改文字/设置后，已打开的浏览器要等 section 重新加载才更新，写进用户说明一行。
4. 继续做 004（主题兼容）。

ASUS 还没做：多地址结账、再次下单、Marketplace 版安装；以及 014（Blank/Hyvä 主题实测，等你 STATUS.md 列出清单和提交号）。
