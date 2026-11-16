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
**环境要求**：ASUS 的预览站要能在后台进程里执行 `bin/magento`（php-cli 可用），`app/code` 目录对 Web/CLI 用户可写；需要本地 HTTP 服务放测试 latest.json（仅 127.0.0.1 / localhost 允许非 https，供测试）。
