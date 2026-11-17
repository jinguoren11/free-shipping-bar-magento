# Theme compatibility / 主题兼容清单

> Status date: 2026-11-16. "Tested" below means tested on a real Magento store by our test team. Nothing is marked tested until a test report says so. / "已测试"指在真实 Magento 站点上由测试组实测；没有测试报告就不会标"已测试"。

## English (for customers)

### Which package do I install?
| Your theme | Install |
|---|---|
| Magento **Luma**, **Blank**, or a theme built on them (most themes) | `getdropflow/module-free-shipping-bar` only |
| **Hyvä** (Alpine.js, Tailwind) | `getdropflow/module-free-shipping-bar` **and** `getdropflow/module-free-shipping-bar-hyva` |
| Not sure | Install the main module; if the bar does not appear, see "If the bar does not appear" below |

The Hyvä module does nothing on non-Hyvä stores, so you may leave it installed if you run both.

> **Hyvä needs BOTH packages.** Without the Hyvä compatibility package the bar simply does not appear on a Hyvä store (it never shows an error).

### Compatibility status
| Theme | Status | Notes |
|---|---|---|
| Magento Luma (2.4.7) | Tested: texts, amounts, states, all three positions | Standard positions: mini cart, cart page, product page |
| Magento Blank | Tested: all three positions, layout, console (test report 014) | A stray bar below the footer was found and fixed (retest pending) |
| Hyvä (default) + compatibility package | Cart page tested; product page and cart drawer positions reworked after test 014, **retest pending** | Cart page, product page (below the add-to-cart row) and cart drawer (above the items) |
| Hyvä (default) without the compatibility package | Tested: nothing is shown, no errors | Install the compatibility package |
| Hyvä (CSP variant) | Written to be CSP friendly (see below); **not yet tested** | |
| Other Luma-based themes (paid or free) | Not yet tested | Usually work; if not, set a custom position |
| Headless / PWA Studio / custom storefronts | **Not supported** | The module renders inside the Magento storefront |

### What the customer sees when the cart is empty
- **Cart page and product page:** "Free shipping on orders over $50.00" (no progress bar).
- **Mini cart / cart drawer:** the bar is **not shown** when the cart is empty (only the theme's own "cart is empty" message).

### Hyvä and CSP
The Hyvä package has no inline scripts and no inline Alpine expressions: the logic is one external file and the page only uses property and method names, which is what Hyvä's CSP build expects. It needs your Content Security Policy to allow scripts from your own site (`script-src 'self'`).

### How it fits your theme
- All style classes start with `gdf-fsb-`, so they do not clash with your theme.
- Text color and font are **inherited from your theme**. Only the progress bar color comes from the module settings.
- Works on narrow screens (phones); long texts wrap.
- The bar needs JavaScript. With JavaScript turned off nothing is shown (and nothing breaks).

### If the bar does not appear where you want it ("custom position")
Stores > Configuration > Sales > Free Shipping Progress Bar > **Theme Compatibility**.
1. Find an element near where you want the bar (your developer or the browser's "Inspect" tool can show its class or id, for example `.page-title-wrapper` or `#maincontent`).
2. Type it as a CSS selector in **Cart Page / Product Page / Mini Cart: Custom Position**, and choose **Placement** (before / after / inside at the start or end).
3. Save and flush the cache.
- If the element cannot be found on the page, the bar is **not shown there** (it never causes an error).
- To go back to the standard position, empty the field.

### Not covered
- The checkout page itself (the bar is for the cart, mini cart and product page).
- After you change a text or setting in the admin, visitors who already have the page open see it when their cart data reloads (see the user guide).
- Themes that remove the standard mini cart and use their own cart widget: use "Mini Cart: Custom Position".
- Free shipping rules that depend on countries, customer groups, or cart price rules are not read (see the user guide).

## 中文（给客户看的）

### 我该装哪个包？
| 你的主题 | 安装 |
|---|---|
| Magento **Luma**、**Blank**，或基于它们的主题（大多数主题） | 只装 `getdropflow/module-free-shipping-bar` |
| **Hyvä** 主题（Alpine.js、Tailwind） | 主模块 **加上** `getdropflow/module-free-shipping-bar-hyva` |
| 不确定 | 先装主模块；不显示的话看下面"进度条没出现在想要的位置" |

Hyvä 兼容包在非 Hyvä 店铺上什么都不做，两种主题并存时可以一直装着。

### 兼容状态
| 主题 | 状态 | 说明 |
|---|---|---|
| Magento Luma（2.4.7） | 已测：文字、金额、各状态、三处位置 | 标准位置：小购物车、购物车页、商品页 |
| Magento Blank | 已测：三处位置、布局、控制台（测试报告 014） | 发现并已修复"页脚下方多一条"，待复测 |
| Hyvä（默认）+ 兼容包 | 购物车页已测；商品页和抽屉位置在测试 014 后重做，**待复测** | 购物车页、商品页（加入购物车行下方）、购物车抽屉（商品列表上方） |
| Hyvä（默认），不装兼容包 | 已测：什么都不显示，不报错 | 请安装兼容包 |
| Hyvä（CSP 版） | 按 CSP 友好方式编写（见下），**尚未测试** | |
| 其他基于 Luma 的主题（收费/免费） | 尚未测试 | 一般可用；不行就设自定义位置 |
| 无头 / PWA Studio / 自建前端 | **不支持** | 本模块在 Magento 自带前端里渲染 |

> **Hyvä 必须同时装两个包。** 不装 Hyvä 兼容包，Hyvä 店铺上就没有进度条（不会报错）。

### 购物车为空时顾客看到什么
- **购物车页、商品页**："订单满 $50.00 免运费"（无进度条）。
- **小购物车 / 购物车抽屉**：购物车为空时**不显示进度条**（只显示主题自带的"购物车为空"）。

### Hyvä 与 CSP
Hyvä 兼容包没有内联脚本、没有内联 Alpine 表达式：逻辑在一个外部文件里，页面上只用属性名和方法名（Hyvä CSP 版要求的写法）。需要你的内容安全策略允许本站脚本（`script-src 'self'`）。

### 它怎么适应你的主题
- 所有样式类名都以 `gdf-fsb-` 开头，不会和主题冲突。
- 文字颜色和字体**继承主题**，只有进度条的颜色来自模块设置。
- 窄屏（手机）可用，长文字自动换行。
- 需要浏览器启用 JavaScript；关闭 JS 时什么都不显示（也不会出错）。

### 进度条没出现在想要的位置（"自定义位置"）
后台：商店 → 配置 → 销售 → 免运费进度条 → **Theme Compatibility（主题兼容）**。
1. 找一个靠近你想放位置的元素（让开发者或用浏览器"检查"工具看它的 class 或 id，如 `.page-title-wrapper`、`#maincontent`）。
2. 把它作为 CSS 选择器填到 **购物车页 / 商品页 / 小购物车：自定义位置**，再选 **相对位置**（之前/之后/内部开头/内部末尾）。
3. 保存并清缓存。
- 页面上找不到这个元素时，进度条**不会显示**（不会报错）。
- 想恢复标准位置：把输入框清空。

### 不覆盖的情况
- 结账页本身（进度条用于购物车、小购物车、商品页）。
- 后台改了文字或设置后，已经打开页面的访客要等他们的购物车数据重新加载才会看到（见用户手册）。
- 去掉了标准小购物车、用自己购物车组件的主题：请用"小购物车：自定义位置"。
- 取决于国家、客户组、购物车价格规则的免运费规则不会被读取（见用户手册）。
