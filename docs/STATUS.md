# STATUS — 云端电脑D（Free Shipping Progress Bar，Magento 2 免费插件）

**最后更新**：2026-11-16　**分支**：`claude/magento-free-shipping-bar-mqm0n2`　**仓库**：jinguoren11/free-shipping-bar-magento

## 当前阶段
第 2–3 阶段完成（代码+单元测试+后台面板）。等待：实机测试（TR-001）、老任几项决定。

## 已完成
- ✅ 官方上架规则研究 → `docs/01-上架规则一页纸.md`
- ✅ 项目计划（功能/文件/测试/上架清单）→ `docs/02-项目计划.md`
- ✅ 模块：后台设置、读取 Magento 免运费门槛（可覆盖）、前台三处进度条（小购物车/购物车页/商品页）、缓存友好
- ✅ 三语（en/es/zh）；一键打包 `build/package.sh`（含 TODO/FIXME 扫描、语法检查）
- ✅ 单元测试 16 项全过（计算、文案、"更多工具"目录、禁止联网代码扫描）
- ✅ 插件内推广规则研究 + 静态"更多工具"后台面板（无联网，可关）→ `docs/04-…md`

## 下一步
1. 等 TR-001 结果（见 `docs/TEST-REQUESTS.md`），按报告修问题
2. phpcs：云端装不上（GitHub 第三方包被网络规则拦截），已列为 **TR-002** 请 ASUS 跑，拿到报告后我修复
3. 客户教程（图文、红框、报错说明）和用户手册 PDF（英文，<5MB）
4. 上架素材：图标、缩略图、≥2 张截图（等 Mac 截图）、长描述（不含推广）、发布说明

## 未验证 / 风险（诚实清单）
- ❗ **还没在真实 Magento 里装过**。以下全是"写了没实测"：三处显示、小购物车区块是否真的出现在 `extra_info`、customer-data 区块加载、后台面板外观、production 编译
- ❗ phpcs 还没跑（TR-002 待派单）。我只做了语法检查和人工对照常见规则，不能算通过
- 切换商店视图后旧语言残留：用 `store` Cookie + `<html lang>` 比对来刷新，需在 TR-001 #9 实测
- 门槛按"优惠后小计"比较（和 Magento Free Shipping 同口径）；含税/不含税设置、"仅限某些国家"的免运费、购物车价格规则里的"免运费"**不读取**（README 已写明，需要的话后续版本再做）

## 问题清单
| # | 问题 | 状态 |
|---|---|---|
| 1 | 云端无法运行 phpcs（网络限制，非代码问题） | 已转 TR-002 |

## 需要老任批准 / 提供的事
1. **15% 服务费条款**（Master Terms 第 3 条）：后台推荐 GetDropFlow 是否可能被 Adobe 视为"通过 Marketplace 营销外部服务"？建议提交前问律师或 Adobe 支持。退路：`show_more_tools` 默认改 0 出"无推广版"。详见 `docs/04-…md`
2. **Vendor Name**（供应商名）：现用 `GetDropFlow`，与账号里不同则要改
3. **欧盟 trader 公开信息**用哪个名称/邮箱/地址（会公开显示）
4. **"更多工具"名单**：其他插件的 名称/一句话/官网链接；并确认 GetDropFlow 那句介绍是否准确、能否对外
5. 开发者账号 PayPal 邮箱（账号资料，我不碰）

## 环境备注
- 云端环境 `free-shipping-bar-magento` 额外放行了：developer.adobe.com、experienceleague.adobe.com、commercemarketplace.adobe.com、www.adobe.com（只为读官方文档）
- 本会话把 `jinguoren11/dropshipkit` 加入了**只读**范围，只读过它的 README，未改任何文件
