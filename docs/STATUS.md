# STATUS — 云端电脑D（Free Shipping Progress Bar，Magento 2 免费插件）

**最后更新**：2026-11-16　**P1 修复提交：`ceb052cd`；主题兼容提交：`f9484aa`；**005/006 修复提交：`5a349036`；007 修复提交：`c5ee79da`（TR-008）；Breeze 提交：`417d1cab`（TR-009）；C1b 修复提交：`475689ed`（TR-010）****　（第 6 阶段：一键升级 + 双渠道；下载分流改为 GitHub Releases）　**分支**：`claude/magento-free-shipping-bar-mqm0n2`　**仓库**：jinguoren11/free-shipping-bar-magento

## 当前阶段
**新铁规矩：每个插件必须一键升级。** 设计已写 `docs/05-一键升级与双渠道设计.md`；**阶段 1 已完成**（渠道构建开关、签名校验、zip 安全检查）；下一步阶段 2（升级引擎）。实机待办：TR-001/002/003（待派单）；TR-004/005（升级成功/失败退回）要等阶段 2–3 实现后才能派单。已读 `from-a:docs/FROM-A/001-owner-decisions.md` 和 README；读过 `docs/TEST-REPORTS.md`：无报告。

## 009 处理完成，提交 `475689ed`
- **C1b（`.header.links` 里条只有 2px 高）已修**：原因——Luma 给该容器设了 `font-size: 0`，条用 `font: inherit` 继承到 0px。现在 `.gdf-fsb-mount` 和 `.gdf-fsb-bar` 都自己设 `font-size: var(--gdf-fsb-font-size, 14px)`（行高、字重、字距也显式设；字体族仍继承主题），手机宽度规则改用 `calc(...)`；新增 `BarCssTest`（两个盒子必须有非零字号、不得 `font: inherit`/`font-size: inherit|0`、无命名颜色，注释不计），把旧写法放回去实测变红。DOM 级渲染测试在云端做不了，需 ASUS 用 `Test/lumachild` 复测（TR-010）。THEMES.md 里"字体继承"的描述需微调：现在是字体族继承、字号固定 14px（可用 CSS 变量改）
- **phpcs 清理（可选项）**：只做了能确定的一项：`bar.css` 第 21 行的命名颜色（`red`/`blue`/`transparent`）改为十六进制/rgba。方法注释、`static` 函数、JS 花括号这几类我**看不到 ASUS 报告第 1 节的明细**（报告不在我的仓库），不盲改；若 A 把那一节贴进 `docs/FROM-A/` 我按清单逐条处理
- **Breeze（008 第 3 项）早已完成**：提交 `417d1cab`，见下节；TR-009 待派单
- 测试：PHP 65 项 + JS 两套全过；四个包重新打包通过

## 008 第 3 项：Breeze（Swissup）支持，提交 `417d1cab`——**已实现，未测试，不宣称可用**
**读了什么（只读）**：Breeze 官方文档（breezefront.com，为此放行了该域名）和公开源码 `swissup/module-breeze`、`swissup/breeze-blank`（克隆在仓库之外，没改）。
**关键发现**
- Breeze 用 `breeze_*` 布局句柄（类似 Hyvä 的 `hyva_`），自带 Knockout 3.5、`uiComponent`、`customer-data`、`mage/cookies`、`Magento_Ui/js/core/app` 的挂载实现；UI 组件写法"和 Luma 一样，只需加 `component` 属性"，并要在 `breeze_default.xml` 里按名字注册（或开 Better Compatibility）
- Breeze 的小购物车模板里区域叫 **`extraInfo`**，不是 Luma 的 `extra_info`——所以 Luma 的小购物车挂载在 Breeze 里不会显示
**决定（做法）**：**不做单独的兼容包**，沿用同一个组件文件（只加了 Breeze 要求的 `component` 属性，Luma 忽略它），主模块新增 Breeze 专用布局文件 `view/frontend/layout/breeze_default.xml`（只在 Breeze 店铺生效，Luma/Hyvä 不受影响）：①按名字注册组件到 `default` 包；②关掉 Luma 的 `extra_info` 那个组件，改把同一组件放进 Breeze 的 `extraInfo` 区域。购物车页/商品页继续用原来的 `Magento_Ui/js/core/app` 挂载（Breeze 支持）。自定义位置（CSS 选择器）仍是兜底。
**后备方案（若 TR-009 失败）**：先为本模块开 Breeze 的"Better Compatibility"；再不行做一个类似 Hyvä 的小型 Breeze 单独包（Alpine 之外用 Breeze 原生写法）。
**没做**：没有预渲染 KO 模板（Breeze 文档说那只是提速；我们的模板在 `web/template`，预渲染路径解析我无法验证，做了反而可能为空）。
**测试**：PHP 61 项（含 `BreezeCompatTest`：布局注册名与 JS 声明名一致、Breeze 句柄不影响其他主题）+ JS 两套（新增断言组件名）全过。**这些只是静态一致性检查，不证明在 Breeze 里能跑。**
**文档**：`docs/THEMES.md` 加 Breeze 小节和表格行（"已写好支持，尚未测试"）；无头前端写明"不支持"（8 第 4 项）；用户手册**暂不提** Breeze（测试通过前不宣称）。
**TR-009** 已写入 `docs/TEST-REQUESTS.md`（8 项，含"Better Compatibility"对照和回退 Luma 回归）。
**我无法确认的点**：Breeze 是否按我写的 `extraInfo` 渲染 jsLayout 子组件；`componentDisabled` 是否能阻止 Luma 区域组件；KO 模板 `GetDropFlow_FreeShippingBar/bar` 在 Breeze 里是否能被取到（若取不到，回退方案见上）。

## 007 处理完成，提交 `c5ee79da`
| 项 | 处理 |
|---|---|
| **X1** phpcs 严重错误 2 个（bar.phtml `XssTemplate.FoundUnescaped`） | 原因：我把转义结果先存进变量再输出，工具要求在输出处转义。两个模板（Luma、Hyvä 兼容包）都改成在每个 `<?=` 处直接 `$escaper->escapeHtmlAttr/escapeUrl(...)`；新增测试扫描所有模板里每个 `<?=` 都必须含转义函数或 `@noEscape`，改回旧写法实测变红。**云端无法跑 phpcs，需 ASUS 确认 0 错误（TR-008 #1）** |
| **R1** 再次下单后条不更新 | ①`etc/frontend/sections.xml` 加了 `sales/order/reorder`、`sales/guest/reorder`、`multishipping/checkout/overviewPost`、`wishlist/index/cart`、`wishlist/index/allcart`、`checkout/cart/addgroup`（这些动作是否都存在我无法在云端核对 Magento 源码；不存在的条目无害）；②**更稳的兜底**：Luma 组件订阅 Magento 自己的 `cart` 区块，购物车一变（不管是哪条路径，包括别的插件）就重新加载我们的区块（200ms 防抖，同一状态不重复加载）；JS 测试覆盖，去掉订阅实测变红。Hyvä 本来整包数据一起重载，不受影响（待 TR-008 #2 确认） |
| **C1** 深色页头文字看不见 | 条改为自带浅色卡片：`color #222`、`background #fff`（完成态浅绿底），字体仍继承；可用 CSS 变量 `--gdf-fsb-text/--gdf-fsb-bg/--gdf-fsb-done-bg` 覆盖。注意：这取代了 004 里"文字颜色继承主题"的做法（继承会在深色页头里变白字白底）；`docs/THEMES.md` 已改 |
| **X2** 后台中西文标签仍是英文 | 原因：i18n 里只有前台文案和少数后台词，系统设置的分组名/字段名/说明没有翻译。补了 es、zh_Hans_CN 各 22 条（所有 label 和 comment，含带 HTML 的说明）；新增测试：system.xml 里每个 label/comment 在 es 和 zh 里都必须有翻译 |

测试：PHP 58 项（278 断言）+ JS 两套全过。TR-008 已登记。

## 005 / 006 处理完成，提交 `5a349036`
| 项 | 处理 |
|---|---|
| **P2** 后台 Messages 说明缺占位符 | 原因：Magento 把说明文字里的 `{{amount}}` 当模板指令吞掉。改成 CDATA + HTML 实体 `&#123;&#123;amount&#125;&#125;`，en/es/zh 三个 i18n 都补了同样的键；新增 `AdminTextsTest`（源文本无裸 `{{`、三种语言各有翻译且含两个占位符）；改回旧写法实测 5 项变红 |
| **N1** 空车行为 | 已写进 `docs/THEMES.md`、用户手册、中文教程；代码也统一：小购物车/抽屉空车时**不显示**条（Luma 和 Hyvä 都有 JS 测试） |
| **N2** 后台改设置后的刷新 | 用户手册和教程各加一段：访客要等购物车数据重新加载（下次操作购物车或 Magento 顾客数据过期，默认 60 分钟）；清缓存不影响浏览器里已有的数据 |
| **H1** Hyvä 抽屉要有条 | 读了 Hyvä 默认主题公开源码（只读，没改）：抽屉有容器 `cart-drawer.items.before`。兼容包新增 Alpine 挂载块放进去（kind=drawer）；空车不显示；设了小购物车自定义位置时抽屉里的退让、只在自定义位置出现一处 |
| **H1b** Hyvä 商品页位置 | 原因：Hyvä 的 `product.info` 块只打印它认识的子块，我挂在外层 `product.info.main` 所以掉到评论表单之后。改挂 `product.info.additional`（该块里加入购物车行的下方、卡片内） |
| **H2** 文档 | `docs/THEMES.md`、用户手册、中文教程都写明"Hyvä 必须同时装兼容包，不装就没有条（不报错）"，故障排查表加一行 |
| **P3** Blank 页脚下方多余的条 | **是 bug，已修**：Luma 的备用挂载点（页脚）在"没设自定义小购物车位置"时本应隐藏，我的判断写反了（`!custom \|\| placed`）。改为 `custom && placed`；新增 Luma 组件 JS 测试 `Test/js/luma-bar.test.js`，把旧写法放回去实测会变红 |
| **CSP 版** | 做的是**CSP 兼容写法**（不是"不支持"）：无内联脚本、无内联 Alpine 表达式；逻辑在外部文件 `hyva-bar.js`，模板指令只用属性名/方法名，样式用 CSS 文件+`el.style`（CSSOM，不受 CSP 限制）。要求站点 CSP 允许本站脚本（`script-src 'self'`）。**未实测**，TR-007 #5 |
| **phpcs 警告 45 条** | 我看不到明细（报告在 ASUS 那边）。按 Magento2 标准常见项自查清理：全部超 120 字符的行（PHP/phtml）已折行；`ReleaseManifest::parse`、`PackageInspector::checkEntries` 拆成小方法降复杂度；文档注释里的泛型类型 `array<…>` 改为 `array`；确认每个方法/属性都有文档注释。**请 ASUS 重跑并把警告清单贴回 TEST-REPORTS**，我按清单逐条处理 |

测试：PHP 55 项（204 断言）+ JS 两套（Luma、Hyvä）全过。TR-007 已登记（Hyvä 抽屉、商品页位置、CSP、Blank、空车、P2、phpcs）。Hyvä 的容器名来自 Hyvä 默认主题当前公开源码；老版本 Hyvä（抽屉容器 1.1.18 之前）可能没有，实测时留意。

## 主题兼容（总管 004）完成，提交 `f9484aa`
- **挂载不依赖主题**：标准布局位置 + 后台 *Theme Compatibility*（购物车页/商品页/小购物车各一个自定义 CSS 选择器 + 相对位置）；找不到元素 → 什么都不显示，不报 JS 错；选择器服务端清洗（拒 `<>{};` 反斜杠、限长）
- **样式**：全部 class 改为 `gdf-fsb-*`；文字颜色/字体继承主题（只有进度条颜色来自设置）；窄屏规则
- **Hyvä 兼容包** `Compat/Hyva`（`GetDropFlow_FreeShippingBarHyva`，Alpine.js，无 Knockout/RequireJS）：`hyva_*` 布局句柄替换 Luma 块；空车状态预渲染进页面（Hyvä 对无会话访客只给空默认数据）；单独打包 `./build/package.sh --module=hyva`（direct / marketplace 各一个包）
- **文档**：`docs/THEMES.md`（中英，给客户）；用户手册和中文教程已加主题段；测试需求 TR-006
- **测试**：PHP 50 项 + JS 行为测试（`node Test/js/hyva-bar.test.js`，已验证改坏逻辑会变红）全过
- **没有实测过任何主题**；THEMES.md 里除 Luma 数据文字外全部标"尚未测试"

### 必须实测的主题清单（请总管派 ASUS；细节见 TR-006）
1. **Luma（默认）**——P1 修复后第一次验证三处位置
2. **Blank**
3. **Hyvä 默认**（主包 + Hyvä 兼容包）
4. **Hyvä CSP 版**（`Hyva/default-csp`，看 CSP 报错）
5. **Luma 子主题**（改过头部/小购物车）
6. **第三方 Luma 系主题 1–2 个**（总管挑当时能免费装的；用来验"自定义位置"）
每个主题查：三处位置、空车首访、360/768px、颜色字体继承、自定义位置（存在/不存在/非法选择器）、控制台无报错。

### Hyvä 兼容包里**我无法在云端验证、需要实测确认**的点
- Hyvä 的商品页是否有 `product.info.main` / `product.info.price`，购物车页是否有 `content` 容器（我按 Magento 标准名写的）
- `hyva_checkout_cart_index` / `hyva_catalog_product_view` 里 `referenceBlock remove` 是否确实去掉 Luma 块
- Hyvä 的 `/customer/section/load` 是否带上我们的 `free-shipping-bar` 区块（理论上全部区块都返回）
- CSP 版：外部脚本 + 仅属性/方法名的指令是否被接受
- **Hyvä 小购物车（抽屉）没有自动挂载点**（文档没查到）：目前只能用"小购物车：自定义位置"；若总管要求自动，需要实测后选挂载点
- 查 Hyvä 文档需要，我给环境放行了 `docs.hyva.io`（只读文档，已记入环境备注）

## 已完成
- ✅ 官方上架规则研究 → `docs/01-上架规则一页纸.md`
- ✅ 项目计划（功能/文件/测试/上架清单）→ `docs/02-项目计划.md`
- ✅ 模块：后台设置、读取 Magento 免运费门槛（可覆盖）、前台三处进度条（小购物车/购物车页/商品页）、缓存友好
- ✅ 三语（en/es/zh）；一键打包 `build/package.sh`（含 TODO/FIXME 扫描、语法检查）
- ✅ 单元测试 16 项全过（计算、文案、"更多工具"目录、禁止联网代码扫描）
- ✅ 客户教程（中文）`docs/user-guide/客户教程.zh.md`（含报错说明、截图清单）；用户手册英文稿 `docs/user-guide/USER-GUIDE.en.md`；PDF 生成脚本 `build/build-guide-pdf.sh`（缺截图时输出 *-DRAFT.pdf，禁止上传）
- ✅ 老任已决定：保守引流（前台无广告；后台一行作者+静态"更多工具"面板可关；上架描述不推广；靠开发者资料网址+GitHub README）；Vendor Name 用 GetDropFlow
- ✅ 用户手册/教程补了"更新版本"一节（Marketplace 版靠 Adobe 更新、插件不自己下载、"更多工具"默认关），符合 001
- ✅ 一键升级阶段 1：`build/package.sh --channel=marketplace|direct` 两个包（Marketplace 包里无任何升级文件且自动扫描通过、"更多工具"默认关）；`ReleaseManifest`（Ed25519 签名+字段+下载地址白名单+版本比较）；`PackageInspector`（大小/sha256/zip 路径安全/版本一致）；`build/sign-release.php`（生成密钥、签名 latest.json）；单测共 30 项全过，并用临时密钥端到端验证过
- ✅ 插件内推广规则研究 + 静态"更多工具"后台面板（无联网，可关）→ `docs/04-…md`

## 下一步
00. 等 ASUS 用最新提交测 TR-001/006（P1 修复后第一次验证位置）；**一键升级阶段 2 要同时覆盖主包和 Hyvä 兼容包**（铁规矩）
0. **一键升级**（见 `docs/05-…md` 第 6 节）：~~阶段1~~ 完成 → 阶段2 升级引擎+预检+读 GitHub Releases（缓存） → 阶段3 设置页升级卡片 → 阶段4 实机 TR-004/005
1. 等 TR-001/002/003 结果，按报告修问题并更新下面"问题清单"
2. 截图回来后放进 `docs/user-guide/images/`，重新生成正式 PDF（去掉 DRAFT）
3. 手册内容在实测后复核：缓存/Varnish、小购物车位置、切换商店视图等说法必须以实测为准，不符就改手册
4. 上架素材：图标、缩略图、≥2 张截图、长描述（不含推广）、发布说明（CHANGELOG）
5. 上架前单独问老任：欧盟 trader 信息

## 未验证 / 风险（诚实清单）
- ❗ **还没在真实 Magento 里装过**。以下全是"写了没实测"：三处显示、小购物车区块是否真的出现在 `extra_info`、customer-data 区块加载、后台面板外观、production 编译
- ❗ 手册里"Varnish/整页缓存可用、三处位置、切换商店视图"等说法**尚未实测**，正式 PDF 前必须核对
- ❗ phpcs 还没跑（TR-002 待派单）。我只做了语法检查和人工对照常见规则，不能算通过
- 切换商店视图后旧语言残留：用 `store` Cookie + `<html lang>` 比对来刷新，需在 TR-001 #9 实测
- 门槛按"优惠后小计"比较（和 Magento Free Shipping 同口径）；含税/不含税设置、"仅限某些国家"的免运费、购物车价格规则里的"免运费"**不读取**（README 已写明，需要的话后续版本再做）

## 说明
- 升级公钥 `Model/Upgrade/ReleaseKey.php` 现在是**空的**（等批准项 D）：在那之前一键升级自动保持关闭，不会访问任何地址。
- README 的下载指向 GitHub Releases；官网页面（说明/下载页）规格见 `docs/05-…md` 第 7 节，仓库公开前不上线。
- `ProjectLinks::PUBLISHED=false`：设置页"点星"行默认不出现，仓库公开后改 true 再发版。
- 新脚本 `build/release-assets.sh`：生成 Release 要附的文件（zip、固定名 zip、签名 manifest.json、说明），只生成不上传。
- Marketplace 包用单独的无推广 README（`README.marketplace.md`）。
- 直接版 zip 里是 `FreeShippingBar/` 一个文件夹（解压到 `app/code/GetDropFlow/`）；Marketplace 版是 Composer 包（文件在 zip 根）。

## 问题清单
| # | 问题 | 状态 |
|---|---|---|
| 1 | 云端无法运行 phpcs（网络限制，非代码问题） | 已转 TR-002；**新提交上请 ASUS 再跑一次** |
| P1 | 进度条从不显示：`getSectionData()` 用 `$base + [...]`，`visible` 仍是 false（ASUS 报告 012） | **已修，提交 `ceb052cd`**：改 `array_merge`，组装逻辑抽到 `Model/SectionDataBuilder`（无 Magento 依赖）；新增 6 项不 mock 的测试覆盖 进度中/已达标/空车=true、虚拟商品/关闭/无门槛=false；把 `array_merge` 改回 `+` 实测 3 项变红；共 37 项测试全过 |
| 2 | Windows 克隆后 `package.sh` 换行符失败 | **已修，提交 `ceb052cd`**：加 `.gitattributes`（*.sh 等强制 LF） |

## 需要老任批准
> 规则：我不在对话里直接问老任；总管拿到答复后写入 `docs/OWNER-DECISIONS.md`，我开工前读它。**没出现在那里的批准一律视为没有批准。**
> 读过 `docs/OWNER-DECISIONS.md`：**文件还不存在**（2026-11-16）。

### 待批准（新增）
**D. 升级签名密钥与 GitHub Release 发布（涉及密钥和对外发布）**
- 要批准什么：①生成升级用的 Ed25519 签名密钥对，私钥由老任/总管保管、不进仓库；②发布 GitHub Release（附 zip、固定名 zip、`manifest.json`、说明）。我只用 `build/release-assets.sh` 产出文件，不上传、不发布。
- 为什么：没有授权服务器，必须靠签名防止附件或账号被动过后给所有商家推送恶意代码；发布 Release 是对外动作。
- 选项：①总管用我提供的脚本生成密钥，私钥放总管指定的安全位置，部署由总管/老任做（**建议**）②老任自己生成 ③先只做开发和测试（用本地测试版本信息），上线前再批。
- 我的建议：③ 现在开始，① 在阶段 4 通过实机测试之后、正式发布之前批。不批准则公钥保持为空，升级功能关闭。

### 待批准
**A.（已由老任在对话中选 2，待总管记录）"更多工具"面板在 Marketplace 版里默认开还是关（涉及 Adobe 15% 服务费条款）**
- 要批准什么：提交 Marketplace 的版本里，`show_more_tools` 默认值。
- 为什么：Master Terms 第 3 条——插件与 Marketplace 之外的服务"相关联"或"通过 Marketplace 营销"该服务，要把该服务总收入 15% 交给 Adobe。后台推荐收费的 GetDropFlow 可能被 Adobe 这样解读；官方没有判例。详见 `docs/04-…md`。
- 选项：①默认开，提交后被问再关 ②**默认关（"无推广版"）上架，GitHub 版默认开** ③提交前先书面问 Adobe Marketplace 支持/律师，得到答复再定。
- 我的建议：**③，答复前先按②准备**（只改 `etc/config.xml` 一行，风险最小）。
- 不批准的后果：我默认按②处理 Marketplace 包。

**B. "更多工具"名单和 GetDropFlow 介绍**
- 要提供：其他插件的 名称 / 一句话 / 官网链接；确认 GetDropFlow 这句介绍准确、可对外："Magento 2 一件代发插件：导入速卖通商品、保持价格与库存同步、回传订单物流单号"（依据 dropshipkit README，该项目可能尚未发布）。
- 选项：①按现句上线 ②老任改句 ③GetDropFlow 未发布前面板先不放它（面板为空则自动不显示）。
- 我的建议：③ 或 ②（避免宣传未发布功能）。不答复则面板只留 GetDropFlow 现句。

**C.（未来）正式提交 Marketplace 审核**
- 这是对外发布动作，需要老任在 Developer Portal 里自己操作或明确批准；账号资料（PayPal 等）由老任自己填，我不碰。条件：TR-001/002/003 通过、PDF 正式版就绪。目前不涉及，仅提前记录。

**E. 仓库转公开（只由老任批准；我不改可见性）**
- 要批准什么：把 `jinguoren11/free-shipping-bar-magento` 从私有改为公开。
- 为什么：一键升级读 GitHub Releases 公开接口，**仓库私有时匿名读不到**；官网下载页和设置页点星链接也要等公开才能上线。
- 前置条件（建议）：TR-001/002/003 通过；TR-004/005 通过；仓库内容复查（无密钥/内部信息，已核对插件代码里没有老任地址等）；README 定稿。
- 选项：①通过后公开 ②先开测试版公开仓库再…（不建议）。我的建议：①，且在公开前由总管跑一遍"仓库公开前检查"。

### 已由总管确认：`from-a` 分支 `docs/FROM-A/001-owner-decisions.md`（2026-11-16）
以下决定已在 001 里写明（A 选 2、一键升级铁规矩、GitHub Releases 分流、仓库保持私有、欧盟信息）；我读完并照办。以后以 FROM-A 目录为准（每步开工前 `git fetch origin from-a`），不再看 OWNER-DECISIONS.md。

（以下是我早先记录的原文，供对照）
0. **A 选 2**：Marketplace 版"更多工具"面板默认关（无推广版）上架；GitHub/官网下载版默认开。
0. **铁规矩（一键升级）**：direct 版做设置页升级卡片（备份→升级→失败自动退回，沿用 GetDropFlow 做法，版本信息和升级包放 getdropflow.com 公开静态文件、不需要授权服务器）；Marketplace 版不带自己下载代码的功能，靠 Adobe 更新，手册写明；两个版本同一份代码、一个开关区分渠道。
0. **下载分流（最新，取代"主下载放官网"）**：主下载放 **GitHub Releases**（每个版本附 zip），官网不另存下载，只放说明/下载页，下载按钮直接指向 Releases 里 zip 的下载地址（浏览器直接下载）；官网下载页和插件设置页底部各放一个"在 GitHub 上点星"链接，**仓库公开前不上线**；引流写在 README 和 Releases 说明里（"更多功能见 GetDropFlow（getdropflow.com）"+ 请点星）；一键升级卡片读 **GitHub Releases 公开信息（缓存）**，仍然备份、出错自动退回；**仓库先保持私有，做好测试后由老任批准再公开，我不改仓库可见性**；Marketplace 版规则不变（无推广版、无自己下载代码的功能）。
1. 保守引流：前台无广告；后台设置页底部一行作者信息 + 静态"更多工具"面板（带开关、不联网）；上架描述不推广；推广靠开发者资料网址 + GitHub README
2. Vendor Name = GetDropFlow
3. 欧盟 trader 公开信息（名称/邮箱/地址，见下方"上架信息"），老任已同意公开显示
（在 OWNER-DECISIONS.md 里确认之前，我只用它们做文档和准备工作，不做任何对外动作。）

## 上架信息（只在 Marketplace 上架表单和插件页使用；**不写进插件代码**）
**欧盟 trader（Developer Portal 个人资料 → Trader information，会公开显示在欧盟用户看到的插件页上；老任已知并同意，与 getdropflow.com 条款页公开信息一致）**
| 字段 | 内容 |
|---|---|
| Business Name | GetDropFlow（运营者 JinGuo Ren） |
| Business Email | support@getdropflow.com |
| Business Address | 10564 Clasico Ct, San Diego, CA 92127-3320, US |
| DUNS Number | 不填（可选） |

其他已定：Vendor Name = GetDropFlow；价格 0；许可证 MIT；标题 Free Shipping Progress Bar。
未定：PayPal 邮箱（账号资料，由老任自己填，我不碰）。
注：这些信息由老任在账号里填写；我这边没有账号权限，不会代填。

## 环境备注
- 云端环境 `free-shipping-bar-magento` 额外放行了：developer.adobe.com、experienceleague.adobe.com、commercemarketplace.adobe.com、www.adobe.com、docs.hyva.io（只为读官方文档）
- 本会话把 `jinguoren11/dropshipkit` 加入了**只读**范围，只读过它的 README，未改任何文件
