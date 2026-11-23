# 免运费进度条：授权你的店铺（1.1.0 版）

从 1.1.0 版起，下载版（GitHub / getdropflow.com）需要用一个**免费的 GetDropFlow 账号**授权一次。插件本身免费，账号只用来知道哪家店铺在用它。店铺没有授权之前，**顾客看不到提示条**；无论是否授权，购物车、结账、订单、价格和运费都不会被碰、不会被锁。

## 四步完成授权

1. 打开 **Stores > Configuration > Sales > Free Shipping Progress Bar**。店铺没有授权时，你只会看到 **Authorization（授权）** 卡片（以及“更新”和“更多工具”卡片）。
2. 点 **登录 GetDropFlow 授权**。会打开一个新标签页（浏览器拦截的话点 **重新打开授权页面**）。设置页上会显示一个代码，例如 `BCDF-GHJK`。
3. 在 GetDropFlow 页面登录（没有账号就免费注册一个），核对代码和后台上的一样，点 **允许**。
4. 回到后台：几秒钟内卡片会变成 **此店铺已授权**，提示条的设置出现。不需要复制任何 Key。

## 会共享什么

只有**店铺网址**和你登录账号的**邮箱**。店铺保存的是它自己的站点令牌（不是你的账号密钥），不会读取你商店的其他任何内容：没有订单、没有顾客、没有商品。详情见 getdropflow.com 的隐私页。

## 规则（什么时候会怎样）

- **新店铺、未授权**：设置页只有授权卡片；前台不显示提示条；购物车、结账、订单照常。
- **免费会员就够**，不收任何费用；GetDropFlow 付费套餐同样可用。
- **官网连不上**（你的服务器断网、官网故障）：已授权的店铺从最近一次成功检查起**继续正常使用 30 天**，卡片显示剩余天数；系统每 6 小时自动重试。只有官网明确回答“此店铺已被移除/撤销”才会立刻锁定。
- **付费套餐到期**：卡片显示蓝色说明；免费会员继续有效，提示条照常使用，不会锁定。
- **断开此店铺**（卡片上的按钮）：忘掉本店保存的凭证，提示条隐藏，直到重新授权。你也可以在 GetDropFlow 账号里移除店铺；下次检查时（最多 6 小时，或点 **立即检查**）店铺会被锁定。
- **从 1.0.0 升级**：没有授权的店铺升级后会被锁定（前台提示条消失），授权一次即可恢复。升级后请做上面的四步。
- **Marketplace 版**（Adobe Commerce Marketplace）不需要授权：它无法连接官网。
- **高级**：如果你已有 GetDropFlow 的许可证 Key，可以展开 **高级：手动填写 Key**，填入后点 **保存并检查**。正常情况请用上面的按钮。

## 常见问题

| | |
|---|---|
| **点按钮没反应 / 没有新标签页** | 浏览器拦截了弹窗。点 **重新打开授权页面**，或允许后台网址的弹窗。 |
| **“此服务器暂时连不上 GetDropFlow 官网”** | 你的服务器打不开 https://license.getdropflow.com（防火墙、DNS、出站连接被禁）。请让主机商放行到该地址的出站 https，然后重试。已授权的店铺仍可继续使用 30 天。 |
| **“代码已过期”** | 超过 10 分钟了。再点一次登录按钮，会生成新代码。 |
| **“你点了拒绝”** | 没有任何更改。重新开始，点 **允许**。 |
| **“尝试次数太多”** | 等几分钟。官网限制了每家店铺询问的频率。 |
| **“GetDropFlow 官网已不再认识此店铺”** | 店铺在你的账号里被移除了，或账号已关闭。请重新登录授权。 |
| **卡片显示已授权但前台没有提示条** | 刷新页面；提示条数据在浏览器里加载并缓存。清一次浏览器的本地存储（Magento 会把旧答案存在那里），或用无痕窗口打开。 |
| **“Key 已保存，但官网暂时还不能确认”** | 过一分钟点 **立即检查**。一直这样说明这个 Key 对本店铺网址无效。 |

## 截图（点击图片放大）

图片由截图任务补上（关键位置用红框）。

<a href="../images/auth-01-locked-card.zh.png"><img src="../images/auth-01-locked-card.zh.png" alt="店铺未授权时的设置页：只显示授权卡片（以及更新、更多工具）。" width="360"></a><br><sub>1. 店铺未授权时的设置页：只显示授权卡片（以及更新、更多工具）。</sub>

<a href="../images/auth-02-code-waiting.zh.png"><img src="../images/auth-02-code-waiting.zh.png" alt="点按钮之后：代码和“等待中”的文字。" width="360"></a><br><sub>2. 点按钮之后：代码和“等待中”的文字。</sub>

<a href="../images/auth-03-website-allow.zh.png"><img src="../images/auth-03-website-allow.zh.png" alt="GetDropFlow 官网页面：会共享什么（店铺网址和账号邮箱）、代码、允许按钮。" width="360"></a><br><sub>3. GetDropFlow 官网页面：会共享什么（店铺网址和账号邮箱）、代码、允许按钮。</sub>

<a href="../images/auth-04-authorized.zh.png"><img src="../images/auth-04-authorized.zh.png" alt="授权之后的卡片：“此店铺已授权”和会员套餐。" width="360"></a><br><sub>4. 授权之后的卡片：“此店铺已授权”和会员套餐。</sub>

<a href="../images/auth-05-offline.zh.png"><img src="../images/auth-05-offline.zh.png" alt="官网连不上时的卡片：剩余天数。" width="360"></a><br><sub>5. 官网连不上时的卡片：剩余天数。</sub>

<a href="../images/auth-06-lapsed.zh.png"><img src="../images/auth-06-lapsed.zh.png" alt="付费套餐到期时的蓝色说明（免费提示条照常使用）。" width="360"></a><br><sub>6. 付费套餐到期时的蓝色说明（免费提示条照常使用）。</sub>

<a href="../images/auth-07-rejected.zh.png"><img src="../images/auth-07-rejected.zh.png" alt="官网已不认识此店铺时的提示。" width="360"></a><br><sub>7. 官网已不认识此店铺时的提示。</sub>

<a href="../images/auth-08-advanced-key.zh.png"><img src="../images/auth-08-advanced-key.zh.png" alt="“高级：手动填写 Key”展开的样子。" width="360"></a><br><sub>8. “高级：手动填写 Key”展开的样子。</sub>

<a href="../images/auth-09-storefront-locked.zh.png"><img src="../images/auth-09-storefront-locked.zh.png" alt="锁定时店铺的购物车页：没有提示条，购物车和结账照常。" width="360"></a><br><sub>9. 锁定时店铺的购物车页：没有提示条，购物车和结账照常。</sub>

