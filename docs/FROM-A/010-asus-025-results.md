# FROM-A 010: ASUS retest results (task 025, module head 432b6fa)

## Result
- **C1b FIXED**: Test/lumachild, mini cart in `.header.links`: bar 54 px high, text 14 px, dark on white. Luma, Blank, Hyvä default regression PASS (360/768 px, en/es/zh, no overflow).
- **phpcs**: 0 errors (main + Hyvä module). Warnings: main 61, Hyvä 2 (was 63).
- **Breeze (TR-009)**: cart page, product page, empty cart, 360/768 px, custom position `#maincontent`, back to Blank: PASS. **FAIL: the mini cart shows NO bar** (with and without Better Compatibility): the `fsb_minicart` mount sits in `.page-wrapper` and stays empty. Only the custom CSS-selector position shows the bar in that case.

## What to do
1. Fix the Breeze mini cart bar. Facts from ASUS: Breeze Evolution 3.1.1 + module-breeze 2.33.1 installed on test site fsb247 (theme currently Blank; you may ask for it to be re-applied). The mount ends up in `.page-wrapper` (the layout region for `extraInfo` is not rendered by the Breeze mini cart template). Look at how Breeze renders its mini cart (its own template/`minicart` component) and attach to a container that really exists there, or document honestly that the Breeze mini cart is not supported and the admin custom position is the way (THEMES.md + manual + tutorial), whichever you can verify from the Breeze public source.
2. phpcs warnings (61, from ASUS list, file: line rule). `MethodAnnotation` = method docblock lacks the annotation Magento2 wants (@param/@return); `StaticFunction` = Magento2 discourages static methods; `Discouraged` = discouraged PHP function:
- `FreeShippingBar/Block/Adminhtml/System/Config/MoreTools.php`: 54 Discouraged.Discouraged
- `FreeShippingBar/Model/AmountFormatter.php`: 30 MethodAnnotation; 31 MethodAnnotation; 37 Discouraged.Discouraged
- `FreeShippingBar/Model/Channel.php`: 20 MethodAnnotation; 21 MethodAnnotation
- `FreeShippingBar/Model/Config.php`: 42 MethodAnnotation; 43 MethodAnnotation; 52 MethodAnnotation; 53 MethodAnnotation; 105 MethodAnnotation; 106 MethodAnnotation
- `FreeShippingBar/Model/MessageBuilder.php`: 14 MethodAnnotation; 15 MethodAnnotation
- `FreeShippingBar/Model/MoreTools/Catalog.php`: 16 MethodAnnotation; 17 MethodAnnotation
- `FreeShippingBar/Model/ProgressCalculator.php`: 18 MethodAnnotation; 19 MethodAnnotation
- `FreeShippingBar/Model/SectionDataBuilder.php`: 41 MethodAnnotation; 42 MethodAnnotation
- `FreeShippingBar/Model/SectionSettings.php`: 28 MethodAnnotation; 29 MethodAnnotation
- `FreeShippingBar/Model/SelectorSanitizer.php`: 19 MethodAnnotation; 20 MethodAnnotation; 32 MethodAnnotation; 33 MethodAnnotation
- `FreeShippingBar/Model/Upgrade/PackageInspector.php`: 18 MethodAnnotation; 19 MethodAnnotation; 27 Discouraged.DiscouragedWithAlternative; 27 Discouraged.Discouraged; 44 MethodAnnotation; 45 MethodAnnotation; 81 MethodAnnotation; 82 MethodAnnotation; 103 MethodAnnotation; 104 MethodAnnotation
- `FreeShippingBar/Model/Upgrade/ReleaseManifest.php`: 65 StaticFunction; 89 StaticFunction; 115 MethodAnnotation; 116 MethodAnnotation; 120 StaticFunction; 136 MethodAnnotation; 137 MethodAnnotation; 142 StaticFunction; 152 MethodAnnotation; 153 MethodAnnotation; 156 StaticFunction; 167 MethodAnnotation; 168 MethodAnnotation; 171 StaticFunction; 176 MethodAnnotation; 177 MethodAnnotation; 180 StaticFunction; 192 StaticFunction; 197 MethodAnnotation; 198 MethodAnnotation; 203 StaticFunction; 205 Discouraged.Discouraged; 206 Discouraged.Discouraged
- `FreeShippingBar/view/frontend/web/js/view/bar.js`: 29 BraceSameLine.BraceOnSameLine; 127 BraceSameLine.BraceOnSameLine
- `FreeShippingBarHyva/view/frontend/web/js/hyva-bar.js`: 15 BraceSameLine.BraceOnSameLine; 146 BraceSameLine.BraceOnSameLine
Clean the MethodAnnotation ones (cheap). For StaticFunction/Discouraged, change only where it is safe and covered by tests; otherwise leave and note why. Do not change behaviour.
3. Note from ASUS test-site gotcha (for TEST-REQUESTS notes): after adding a theme, deploy static content for ALL themes (`setup:static-content:deploy -f en_US es_ES zh_Hans_CN fr_FR`).
4. Then ASUS retests Breeze mini cart (A will dispatch). Report on a branch/STATUS only, silent in chat.
