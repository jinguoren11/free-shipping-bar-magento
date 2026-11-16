# STATUS — 云端电脑D（Free Shipping Progress Bar，Magento 2 免费插件）

**最后更新**：2026-11-16（第 5 阶段：教程与手册完成文字稿）　**分支**：`claude/magento-free-shipping-bar-mqm0n2`　**仓库**：jinguoren11/free-shipping-bar-magento

## 当前阶段
文字稿阶段完成。**卡在等实机**：TR-001（冒烟+三语+状态）、TR-002（phpcs）、TR-003（教程截图），三项都待总管派单。读过 `docs/TEST-REPORTS.md`：目前无报告。

## 已完成
- ✅ 官方上架规则研究 → `docs/01-上架规则一页纸.md`
- ✅ 项目计划（功能/文件/测试/上架清单）→ `docs/02-项目计划.md`
- ✅ 模块：后台设置、读取 Magento 免运费门槛（可覆盖）、前台三处进度条（小购物车/购物车页/商品页）、缓存友好
- ✅ 三语（en/es/zh）；一键打包 `build/package.sh`（含 TODO/FIXME 扫描、语法检查）
- ✅ 单元测试 16 项全过（计算、文案、"更多工具"目录、禁止联网代码扫描）
- ✅ 客户教程（中文）`docs/user-guide/客户教程.zh.md`（含报错说明、截图清单）；用户手册英文稿 `docs/user-guide/USER-GUIDE.en.md`；PDF 生成脚本 `build/build-guide-pdf.sh`（缺截图时输出 *-DRAFT.pdf，禁止上传）
- ✅ 老任已决定：保守引流（前台无广告；后台一行作者+静态"更多工具"面板可关；上架描述不推广；靠开发者资料网址+GitHub README）；Vendor Name 用 GetDropFlow
- ✅ 插件内推广规则研究 + 静态"更多工具"后台面板（无联网，可关）→ `docs/04-…md`

## 下一步
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

## 问题清单
| # | 问题 | 状态 |
|---|---|---|
| 1 | 云端无法运行 phpcs（网络限制，非代码问题） | 已转 TR-002 |

## 需要老任批准
> 规则：我不在对话里直接问老任；总管拿到答复后写入 `docs/OWNER-DECISIONS.md`，我开工前读它。**没出现在那里的批准一律视为没有批准。**
> 读过 `docs/OWNER-DECISIONS.md`：**文件还不存在**（2026-11-16）。

### 待批准
**A. "更多工具"面板在 Marketplace 版里默认开还是关（涉及 Adobe 15% 服务费条款）**
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

### 老任已在对话中直接给出、**尚待总管抄进 OWNER-DECISIONS.md 确认**的决定
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
- 云端环境 `free-shipping-bar-magento` 额外放行了：developer.adobe.com、experienceleague.adobe.com、commercemarketplace.adobe.com、www.adobe.com（只为读官方文档）
- 本会话把 `jinguoren11/dropshipkit` 加入了**只读**范围，只读过它的 README，未改任何文件
