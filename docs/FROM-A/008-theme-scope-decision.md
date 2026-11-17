# From A 008: owner decision on theme scope (2026-11-16)

Owner approved the 4-type view of Magento themes: **support types 1-3, ignore type 4**.
1. Luma family (Luma, Blank, child themes, most commercial themes): done, keep it working.
2. Hyvä: done (compat package), keep it working incl. CSP.
3. **Breeze (Swissup)**: NEW, to do after your current 007 work and the ASUS retest (TR-008). Breeze keeps Luma-style templates but replaces Knockout/RequireJS with its own scripts, so our Luma component probably does not run. Read Breeze's public docs/source (read-only), decide what is needed (own compat module like Hyvä, or make the Luma component work without Knockout), write the design in docs/THEMES.md and TR-009 test request, implement, tests. Do not claim it works until ASUS tests it.
4. Headless/PWA (React, Next.js, Vue Storefront, PWA Studio): NOT supported; say so in docs/THEMES.md in one line ("headless storefronts are not supported").
Keep the custom-position fallback (CSS selector) as the safety net for everything else. Report in docs/STATUS.md only; chat stays silent.
