# TEST-REQUESTS（测试请求单）— 由「云端电脑D」写，总管派给 ASUS / Mac

规则：请求只追加不改旧的；结果写到 `docs/TEST-REPORTS.md`，按请求编号对应。

---
## TR-001 ｜ 状态：待派单 ｜ 第一轮冒烟 + 三语 + 全部购物车状态

**包的位置**
- 仓库：`jinguoren11/free-shipping-bar-magento`，分支 `claude/magento-free-shipping-bar-mqm0n2`（最新提交即可）
- 取包：`git clone -b claude/magento-free-shipping-bar-mqm0n2 https://github.com/jinguoren11/free-shipping-bar-magento` 后执行 `./build/package.sh`，得到 `dist/getdropflow-module-free-shipping-bar-1.0.0.zip`
- 安装：zip 解压到 Magento 的 `app/code/GetDropFlow/FreeShippingBar/`（目录里直接是 `etc/`、`Model/`、`registration.php` 等，不要多套一层）

**环境（ASUS）**
- Magento Open Source 2.4.7 或 2.4.8，PHP 8.2 或 8.3，带示例商品（要有简单商品、可配置商品、虚拟商品各至少 1 个）
- 命令：`bin/magento module:enable GetDropFlow_FreeShippingBar && bin/magento setup:upgrade && bin/magento deploy:mode:set production && bin/magento cache:flush`
- **第一个要看的结果**：以上命令是否有任何报错（尤其 production 编译 di:compile、静态文件部署）。有报错请把完整报错原文贴回报告，其余测试先不用做。
- 后台：商店→配置→销售→交付方式→Free Shipping：启用，最低订单金额 = 50
- 三个商店视图：English（美元）、Español（欧元）、中文（人民币），装对应语言包；已开启整页缓存（有 Varnish 更好）

**要测的语言**：en_US、es_ES、zh_Hans_CN（另外加测一个没有翻译的语言，例如 fr_FR，预期回退为英文）

**要测的状态（每种语言、三个位置都要看：小购物车 / 购物车页 / 商品页）**
| # | 状态 | 预期 |
|---|---|---|
| 1 | 空购物车 | 显示"满 $50.00 免运费"，无进度条 |
| 2 | 购物车 $10 | "再买 $40.00 即可免运费"，进度条约 20% |
| 3 | 购物车 $49.99 | 差 $0.01，进度条 99% |
| 4 | 刚好 $50.00 | 显示已享受免运费，进度条 100% |
| 5 | 超过 $50（如 $120） | 显示已享受免运费 |
| 6 | 只有虚拟商品 | 三处都**不显示**条 |
| 7 | 用优惠券让金额降到 50 以下 | 刷新前后都变回"再买 $X"（不必刷新整页，小购物车/购物车页自动更新） |
| 8 | 改数量、删商品 | 条自动更新，不刷新整页 |
| 9 | 切换币种/商店视图 | 金额按汇率换算，文字换成对应语言（先在英文视图加购，再切到西班牙/中文视图，看是否变成对应语言而不是旧语言） |
| 10 | 后台把 Free Shipping 关闭 | 三处都不显示 |
| 11 | 后台设置：关掉"小购物车"/"购物车页"/"商品页"各一个 | 对应位置消失，其他保留 |
| 12 | 后台设置：改成自定义门槛 80 | 全部按 80 计算 |
| 13 | 后台填自定义文案（含 `{{amount}}`） | 前台按文案显示，金额被替换 |
| 14 | 未登录 / 已登录 | 表现一致 |
| 15 | 整页缓存开启下刷新 | 页面可以被缓存，条仍显示正确金额 |
| 16 | 后台设置页（商店→配置→销售→免运费进度条）底部 | 有灰边小框"更多工具/More tools/Más herramientas"，列出 GetDropFlow 和链接 getdropflow.com；把"Show More tools Panel"改为"否"后面板消失；三语显示正确 |
| 17 | Magento 回归（官方审核会跑） | 游客下单（简单+可配置）、多地址结账、从旧订单再次下单、加心愿单、加比较、后台开发票/发货 都正常，没有被插件影响 |

**截图（Mac）**：每种语言各拍：状态 1、2、4 的 小购物车/购物车页/商品页（共 9 张×3 语言）+ 后台设置页（含"更多工具"）。文件名：`语言_位置_状态.png`。有问题处用红框标出。截图不要带网址栏。

**一并回传**：浏览器控制台（F12 Console）报错原文；`var/log/system.log`、`exception.log` 里新增的与本插件有关的行。

**预期结果**：以上全部通过；控制台无本插件相关报错。

---
## TR-002 ｜ 状态：待派单 ｜ 官方代码规范检查（phpcs，Marketplace 同款）

云端装不了 phpcs（第三方包下载被网络规则拦截），请在 ASUS 上跑。可与 TR-001 同机、同一次完成。

**包的位置**：同 TR-001（同分支）。
**命令**（在装有 `magento/magento-coding-standard` 的环境里，路径换成插件目录）：
```
phpcs --standard=Magento2 --extensions=php,phtml --error-severity=10 --ignore-annotations --report=json --report-file=report.json app/code/GetDropFlow/FreeShippingBar
```
另跑一次看全部警告（不会拒审，但我想修）：
```
phpcs --standard=Magento2 --extensions=php,phtml app/code/GetDropFlow/FreeShippingBar
```
**预期结果**：severity≥10 的 Error 为 0（有任何 Error 就会被 Marketplace 拒）。请把 `report.json` 和警告输出原文贴回 `docs/TEST-REPORTS.md`。

---
## TR-003 ｜ 状态：待派单（可与 TR-001 同一次完成）｜ 教程截图

**包的位置**：同 TR-001。
**要拍的图**：见 `docs/user-guide/客户教程.zh.md` 末尾"截图清单"（01–08、E1，共 9 组）。
**规则**：红框用红色矩形 #FF0000、线宽 3px；不要带网址栏和浏览器边框；PNG，每张 <5MB；文件放到 `docs/user-guide/images/`，文件名用清单里的名字加后缀（如 `01-overview.png`、`07-es.png`、`08-zh.png`、`E1.png`）。
**预期**：每张图一眼能看出红框里的内容；英文界面为主，07/08 为西/中。

---
## TR-004 / TR-005 ｜ 状态：**暂缓，阶段 2–3 实现后再派单**（先登记，让总管知道要准备的环境）｜ 一键升级

**TR-004 升级成功**：旧版（1.0.0 direct 包）→ 新版（我会另出 1.0.1 direct 测试包 + 本地测试用 latest.json，**不碰 getdropflow.com 正式站**）。预期：卡片"检查更新"→显示新版→点"一键升级"→进度→完成；前台进度条正常；版本号变 1.0.1；维护模式已退出；旧目录备份在位；`bin/magento module:status` 已启用。
**TR-005 失败自动退回**（各做一次）：①包被篡改（sha256 不符）②latest.json 签名不对 ③坏包（换文件后编译失败）④升级中途 `kill` 后台进程。预期：全部退回 1.0.0，站点可正常访问，卡片显示原因，维护模式已退出；无法退回时保持维护模式并显示恢复命令。
**预检**：目录不可写 / Composer 安装的实例 → 按钮置灰 + 原因。
**Marketplace 版**：包内无升级相关文件、设置页无升级卡片。
**环境要求**：ASUS 的预览站要能在后台进程里执行 `bin/magento`（php-cli 可用），`app/code` 目录对 Web/CLI 用户可写；需要在预览站本机起一个本地 HTTP 测试服务（仅 127.0.0.1 / localhost 允许非 https，供测试）。

---
## TR-006 ｜ 状态：待派单（依赖 P1 修复提交；可与 TR-001 同机）｜ 主题兼容（详见 docs/THEMES.md）

**包的位置**：同 TR-001 的分支，最新提交（见 docs/STATUS.md 顶部提交号）。主包 `./build/package.sh --channel=direct`；Hyvä 兼容包 `./build/package.sh --channel=direct --module=hyva`（输出 `dist/getdropflow-module-free-shipping-bar-hyva-1.0.0-direct.zip`，解压到 `app/code/GetDropFlow/FreeShippingBarHyva/`，再 `module:enable GetDropFlow_FreeShippingBarHyva`、`setup:upgrade`、`cache:flush`）。
**要测的主题（按优先级）**：
1. **Luma（默认）**——标准位置（小购物车、购物车页、商品页）能看到条（P1 修复后第一次验证位置）
2. **Blank**
3. **Hyvä 默认主题**（装主包 + Hyvä 兼容包）
4. **Hyvä CSP 版**（Hyva/default-csp，看浏览器控制台有没有 CSP 报错）
5. 一个**子主题**（继承 Luma，改过头部/小购物车区域）
6. 一个**第三方主题**：总管挑 1–2 个当时能免费装到的 Luma 系主题/演示主题（我不指定具体名字）
**每个主题都做**：
| # | 检查 | 预期 |
|---|---|---|
| a | 三处位置（Hyvä 的小购物车除外）购物车 $10 | 显示"再买 $40 免运费"，进度条 20% |
| b | 空购物车、首次访问（无会话的新访客，清 Cookie 后） | 显示"满 $50 免运费"（Hyvä 靠页面里预先渲染的空车状态） |
| c | 手机宽度 360px 和 768px | 不溢出、不遮挡，文字换行正常 |
| d | 颜色/字体 | 文字颜色和字体与主题一致（不是写死的颜色）；暗色背景主题上文字仍可读 |
| e | 自定义位置：填一个**存在**的选择器（如 `#maincontent`），相对位置分别选 4 种 | 条出现在对应位置 |
| f | 自定义位置：填**不存在**的选择器（`.nope`）和**非法**选择器（`<<<`、`a{b}`） | 该位置不显示条；控制台**无 JS 报错**；其它位置正常 |
| g | 清空选择器 | 回到标准位置 |
| h | 小购物车自定义位置（Hyvä 和 去掉了标准小购物车的主题） | 条出现在填的位置 |
| i | 控制台（F12）| 无与 gdf / free-shipping-bar 相关的报错 |
| j | Hyvä：切换商店视图（英→西） | 文字/币种随商店视图变化（需要至多刷新一次） |
**预期结果**：以上通过；不通过的写明主题名、位置、浏览器控制台原文、截图（红框）。

---
## TR-007 ｜ 状态：待派单 ｜ 复测（014 之后的修复）｜ 依据 FROM-A 005/006

**包的位置**：同分支最新提交（见 docs/STATUS.md 顶部提交号）。主包 `./build/package.sh --channel=direct`；Hyvä 包 `./build/package.sh --channel=direct --module=hyva`。
| # | 主题 | 检查 | 预期 |
|---|---|---|---|
| 1 | Hyvä 默认 + 兼容包 | **购物车抽屉**（加一件商品，打开抽屉） | 抽屉里商品列表上方有进度条；空车时抽屉里没有条 |
| 2 | Hyvä 默认 + 兼容包 | **商品页位置** | 条在价格/加入购物车行下方（卡片内），不在评论表单之后 |
| 3 | Hyvä 默认 + 兼容包 | 购物车页（回归）、360px | 仍正常，无溢出 |
| 4 | Hyvä 默认 + 兼容包 | 小购物车自定义位置：填一个存在的选择器（如 `#maincontent`） | 抽屉里的条消失，条出现在填的位置（只出现一处，不重复） |
| 5 | **Hyvä CSP 版**（Hyva/default-csp） | 开着 CSP 看控制台；购物车页、商品页、抽屉 | 能显示条；控制台无 CSP 违规（若有，贴原文） |
| 6 | **Blank** | 没设自定义小购物车位置时，页脚下方 | **没有**多余的条（P3 修复） |
| 7 | Luma、Blank | 空车：购物车页、商品页、小购物车 | 前两者有"满 $50 免运费"，小购物车只有 Magento 自带的"购物车为空" |
| 8 | 任一 | 后台 Messages 分组说明文字（en/es/zh） | 显示 `{{amount}}` 和 `{{threshold}}` 两个占位符名称（P2 修复） |
| 9 | 任一 | **phpcs 再跑一遍** | 严重错误 0；**请把警告完整清单贴回 TEST-REPORTS**（我看不到明细，无法逐条清理；上次 45 条，我已按行长、文档注释、复杂度做了清理） |

---
## TR-008 ｜ 状态：待派单 ｜ 复测 007 的四项修复

**包的位置**：同分支最新提交（见 docs/STATUS.md 顶部）。
| # | 检查 | 预期 |
|---|---|---|
| 1 | **X1** phpcs（`--error-severity=10`） | 0 个错误（bar.phtml 的 XssTemplate 已修；Hyvä 兼容包模板同样处理）；请把警告清单贴回 TEST-REPORTS |
| 2 | **R1** 订单历史点"再次下单"（Luma、Blank、Hyvä 各一次）；再测 心愿单"全部加入购物车"、多地址结账完成后再购物 | 购物车 $130 时条显示"已达标"、100%，不再是"再买 $50"0% |
| 3 | **C1** 深色页头的 Luma 子主题，打开小购物车 | 条的文字清晰可读（深色字、浅底卡片） |
| 4 | **X2** 后台切到西班牙文、中文 | 设置页所有分组名、字段名、说明文字都是对应语言 |

---
## TR-009 ｜ 状态：待派单（在 TR-008 之后）｜ Breeze 主题（Swissup）实测

**包的位置**：同分支最新提交（见 docs/STATUS.md 顶部）。**只装主包**（`./build/package.sh --channel=direct`），不需要 Hyvä 包；解压到 `app/code/GetDropFlow/FreeShippingBar/`，`module:enable`、`setup:upgrade`、`cache:flush`。
**环境**：Magento 2.4.7，Breeze 主题（`swissup/breeze-blank` 主题或 Breeze Evolution，主题包公开可装；核心 `swissup/module-breeze`）。在后台 内容 > 设计 > 配置 里启用 Breeze 体验。已知这是我们没碰过的 JS 栈，**请把任何失败原样记录，不要为了通过去改配置**；先测默认配置，再按下表第 6 行测"Better Compatibility"。
| # | 检查 | 预期 |
|---|---|---|
| 1 | 购物车 $10：购物车页、商品页 | 显示"再买 $40 免运费"，条 20% |
| 2 | 小购物车（打开迷你购物车） | 商品列表附近有进度条；空车时没有 |
| 3 | 空车：购物车页、商品页 | "满 $50 免运费"，无进度条 |
| 4 | 加减数量、删商品、用优惠券 | 条实时更新，不刷新页面 |
| 5 | 浏览器控制台（F12）、360px 宽度 | 无与 gdf / free-shipping-bar 相关的报错；不溢出 |
| 6 | 若上面任何一项不显示：后台 内容 > 设计 > 配置 > Breeze > 勾选"Better Compatibility"，清缓存再测 | 记录是否因此恢复显示（告诉我哪项需要它） |
| 7 | 自定义位置兜底：填 `#maincontent`，相对位置"之前" | 条出现在该位置 |
| 8 | 与 Hyvä/Luma 无关的回归：同一站点换回 Luma 主题 | 仍正常（Breeze 专用布局不影响 Luma） |
**回传**：每项 通过/未通过、控制台原文、截图。预期在 Breeze 上"可能能用也可能不能用"，请据实报告。

---
## TR-010 ｜ 状态：待派单 ｜ 复测 C1b（迷你购物车被主题移进 `.header.links`）

**包的位置**：同分支最新提交（见 docs/STATUS.md 顶部）。**环境**：ASUS 的 fsb247 上保留的子主题 `Test/lumachild`（把迷你购物车移到 `.header.links`，该容器 `font-size: 0`）。
| # | 检查 | 预期 |
|---|---|---|
| 1 | 购物车里有商品，打开小购物车 | 进度条可见，文字约 14px、完整显示，不是 2px 高的一条线 |
| 2 | 同一子主题的购物车页、商品页 | 仍正常 |
| 3 | Luma、Blank、Hyvä 回归（小购物车/抽屉） | 字号和之前一致，无异常 |
| 4 | phpcs 再跑 | 错误 0；警告数请记录（本次只处理了 bar.css 的命名颜色，其余注释/静态函数类警告需要清单才能逐条处理） |

---
## TR-011 ｜ 状态：待派单 ｜ 复测 Breeze 小购物车 + phpcs 警告

**包的位置**：同分支最新提交（见 docs/STATUS.md 顶部）。环境同 TR-009（Breeze Evolution + module-breeze，请 A 把主题重新应用到 fsb247）。
**测试站注意（ASUS 025 的经验）**：新增主题后要给**所有**主题部署静态文件：`bin/magento setup:static-content:deploy -f en_US es_ES zh_Hans_CN fr_FR`，否则新主题缺静态资源，结果会误判。
| # | 检查 | 预期 |
|---|---|---|
| 1 | Breeze，购物车 $10，不设自定义位置，打开小购物车 | 小购物车里（附加信息区）有进度条；空车时没有 |
| 2 | 同上，开/关 Better Compatibility 各一次 | 都有条（若只有开着才有，请记录） |
| 3 | 小购物车自定义位置填一个存在的选择器 | 默认区域的条消失，条出现在填的位置（只一处） |
| 4 | Breeze 购物车页、商品页（回归） | 仍正常 |
| 5 | Luma、Blank、Hyvä（回归，含 C1b 的 Test/lumachild） | 仍正常 |
| 6 | phpcs（`--error-severity=10` 与完整警告） | 错误 0；警告数应明显少于 61（已处理 MethodAnnotation、静态函数、JS 花括号、`parse_url`、`strip_tags`） |

---
## TR-012 ｜ 状态：待派单 ｜ 一键升级引擎（阶段 2–3）的实机测试 = TR-004 / TR-005 ｜ 同时复测 TR-011

**包**：分支 `fsb-fixes`（PR 里）最新提交；`./build/package.sh --channel=direct` 的 zip。**只用测试站，不碰正式站、不碰真实 GitHub。**
**为什么要本地服务器**：仓库是私有的，匿名读不到 Releases；升级器默认只认 `github.com/jinguoren11/free-shipping-bar-magento` 的地址。测试用的后门只有一个：环境变量 `GETDROPFLOW_FSB_TEST_RELEASES=http://127.0.0.1:端口/`（只接受本机地址）；要让 web 和命令行都看到它（php-fpm 的 `env[...]` 和 shell 里都设）。
**准备**
1. 用 `php build/sign-release.php genkey` 生成**测试用**密钥对；把测试公钥写进**测试包**里的 `Model/Upgrade/ReleaseKey.php`（只改测试包，不改仓库；正式密钥是待老任批准的 D 项）。
2. 先装版本 1.0.0 的测试包（手动安装）。再用 `build/package.sh` 把 `composer.json` 里的版本临时改成 1.0.1 打一个 1.0.1 的 direct 包（只在测试机上改，不提交）。
3. 本机起一个静态文件服务（例如 `python3 -m http.server 8099`），目录里放：`latest.json`（模仿 GitHub 的 `releases/latest` 返回：`{"assets":[{"name":"manifest.json","browser_download_url":"http://127.0.0.1:8099/manifest.json"}]}`）、`manifest.json`（用 `sign-release.php` 签名，`url` 指向 `http://127.0.0.1:8099/pkg.zip`，sha256/字节数与 1.0.1 zip 一致）、`pkg.zip`。
| # | 检查 | 预期 |
|---|---|---|
| 1 | 打开设置页（未点按钮） | 顶部"更新"卡片：已安装版本 1.0.0，提示"点检查更新才联网"；服务器日志里没有任何请求 |
| 2 | 点"检查更新" | 显示最新版本 1.0.1 和更新说明（按后台语言 en/es/zh）；有"立即升级（一键）"按钮 |
| 3 | 立即点"检查更新"第二次 | 10 分钟内不再请求服务器（看服务器日志） |
| 4 | **TR-004 成功**：点"立即升级"并确认 | 卡片显示第几步/共几步；维护模式出现几分钟；完成后"已升级到 1.0.1"，刷新后版本 1.0.1；进度条在三个主题上仍正常；`var/getdropflow_fsb_upgrade/previous-FreeShippingBar` 已删除；维护模式已退出 |
| 5 | **TR-005 失败退回 A**：把 manifest 里 sha256 改错（重新签名）再升级 | 下载后立即拒绝，卡片写原因；**没有进入维护模式**，文件没动 |
| 6 | **TR-005 失败退回 B**：1.0.1 包里放一个会让 `setup:upgrade` 报错的文件（例如损坏的 `etc/module.xml` 版本不匹配或语法错误的 `etc/di.xml`） | 自动退回 1.0.0，卡片显示"已退回"和原因，维护模式已退出，商城正常 |
| 7 | 签名不对（用另一把密钥签 manifest） | 卡片写"版本信息不是作者签名的"，没有下载 |
| 8 | 中途杀掉后台进程（`pkill -f getdropflow:fsb:upgrade`）在"安装"步骤 | 1 小时后（或把状态文件时间改旧）再打开卡片：自动退回旧版并退出维护模式 |
| 9 | 站点在维护模式（升级前手动 `maintenance:enable`）时升级 | 升级完成后仍保持维护模式（不替老任关掉） |
| 10 | 模块装在别处/文件夹只读 | 卡片灰掉并写明原因，给手动升级说明，不动任何文件 |
| 11 | Marketplace 包（`--channel=marketplace`）装上 | 设置页没有"更新"卡片；`grep -r` 没有 curl/ZipArchive/api.github.com；命令列表里没有 `getdropflow:fsb:upgrade` |
| 12 | phpcs 再跑 | 错误 0；记录警告清单 |
同时做 **TR-011**（Breeze 小购物车、phpcs、回归）。

---
## TR-013 ｜ 状态：待派单 ｜ 在**原样的包**上重跑 TR-012 #4–#9 + phpcs（修复 ASUS 068 的问题 1–7）

**包**：分支 `fsb-fixes-2`（PR 里）最新提交；`./build/package.sh --channel=direct`（及 marketplace、hyva）。**必须是原样的包：不许给测试包打任何补丁**；测试用公钥仍可只替换 `Model/Upgrade/ReleaseKey.php`（测试公钥，仓库不动），本机发布服务和环境变量同 TR-012。PHP 环境刻意保持 068 的样子：**php-fpm 是 8.3，`/usr/bin/php` 默认是 8.5**（不要改默认 php，这是在测问题 2）。
| # | 检查 | 预期 |
|---|---|---|
| 1 | 原样包：点"立即升级"并确认（= TR-012 #4） | 卡片立刻显示"第 1 步，共 6 步"并往下走（**不再停在"第 0 步 Starting"**，手动跑 `getdropflow:fsb:upgrade` 也不再报 ALREADY_RUNNING）；后台进程用的是 php8.3（`ps` 里看）；完成后 1.0.1，`previous-FreeShippingBar` 已删，维护模式已退出 |
| 2 | 步骤名：把后台语言切到中文和西语各走一次升级 | 步骤名是中文/西语（"下载并检查新版本"…），不是英文；维护模式的 2 分钟里卡片显示"维护模式中，会自动继续"，**页面顶部没有 Magento 的红色错误条**；结束后自动显示"完成" |
| 3 | 让 php8.3 暂时找不到（例如临时把 `/usr/bin/php8.3` 改名，php-fpm 不受影响，**之后改回**） | 卡片/按钮给出原因"没有找到与本网站相同版本的 PHP"（`PHP_NOT_FOUND`），不升级；改回后正常 |
| 4 | 让后台进程起不来（例如临时让 `bin/magento` 不可执行或让命令立即退出） | 30 秒内卡片写"升级进程 30 秒内没有启动，没有做任何改动"；再点可重试；**不再出现 1 小时内一直"已有升级在运行"** |
| 5 | sha256 改错并重新签名后升级（= TR-012 #5） | 卡片写"下载的文件与校验码不一致……没有做任何改动"（不写"已恢复"）；**没有**进维护模式；日志里**没有** `setup:upgrade`/`di:compile`/`static-content:deploy`/`cache:flush`（可以用 `ps`/命令日志/generated 目录时间戳确认没重建）；`var/getdropflow_fsb_upgrade/` 里没有 `package.zip`/`new/` |
| 6 | 1.0.1 包里 `etc/di.xml` 语法错误（= TR-012 #6，**原样包**，不打补丁） | 自动退回 1.0.0，卡片"升级没有成功，已恢复为你之前的版本"+原因；维护模式退出；前台 200 |
| 7 | 在"安装"步骤 `kill -9` 工作进程和 setup 子进程；**后台 IP 不在维护模式豁免名单**（= TR-012 #8） | ①什么都不做，等 cron（先确认 `bin/magento cron:run` 在跑，或手动 `cron:run` 两次）：约 5–7 分钟内自动恢复，版本回 1.0.0，维护模式退出，`new/`、`package.zip` 已清；②或者命令行 `php8.3 bin/magento getdropflow:fsb:upgrade --recover`：同样结果；③恢复后打开后台，卡片显示"升级被中断……已恢复" |
| 8 | 站点本来就在维护模式时升级（= TR-012 #9，**原样包**） | 升级完成后维护模式仍开着，豁免 IP 也保留；卡片"完成" |
| 9 | 全部语言（en/es/zh）：BAD_CHECKSUM、BAD_ZIP（把 pkg.zip 换成文本文件并改 manifest）、DOWNLOAD_FAILED（让 pkg.zip 404）、签名不对 各触发一次 | 每种都有该语言的原因文字，且文字不说"已恢复"（因为没改动） |
| 10 | Marketplace 包（= TR-012 #11） | 仍没有任何升级代码：包里没有 `Cron/`、`etc/crontab.xml`、`Console/`、`Controller/`、`Model/Upgrade/`；`bin/magento list` 没有该命令；`cron_schedule` 里没有 `getdropflow_fsb_recover_upgrade` |
| 11 | 直接版装好后 `cron_schedule` | 有 `getdropflow_fsb_recover_upgrade`，没有升级在跑时每次都是 success 且瞬间完成（不做任何事） |
| 12 | phpcs：Magento2 标准，直接版包解开后跑 `--error-severity=10`，**再用 `--ignore-annotations` 跑一次** | 错误 0（`--ignore-annotations` 下 `proc_open`/`exec`/`@rename`/`@disk_free_space` 这四类已经真的不存在）；请记录警告总数和按类别分布（旧数：完整跑警告 95） |
同时回归：Luma / Blank / Hyvä / Breeze 的条（升级代码不应影响前台）。
