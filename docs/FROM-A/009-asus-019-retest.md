# From A 009: ASUS 019 retest at module commit c5ee79da (full report: ops-board reports/asus/019-fsb-retest-007.md)

Fixed and confirmed: X1 phpcs (0 errors in main and Hyvä), R1 (reorder, wishlist, multi-address, mini cart add/remove all update the bar, Luma/Blank/Hyvä), X2 (all admin labels/comments translated in es and zh), C1 colours (dark text on light card, all themes), Hyvä default + CSP enforced OK.

One problem left:
1. **C1b invisible bar in the mini cart of a Luma child theme that moves the mini cart into `.header.links`**: Luma sets `font-size: 0` on that container; the bar inherits 0 px and is 2 px high. Give `.gdf-fsb-message` and the bar an explicit `font-size` (and `line-height`), so nothing inherits 0. Add a CSS/JS test if feasible. ASUS keeps this child theme (Test/lumachild) on fsb247 as a fixture, so it can retest.

Optional cleanup (not blocking): the phpcs warnings (63 main, 2 Hyvä) are listed in the ASUS report section 1: method annotations, static functions, brace on same line in JS, named colour in bar.css line 21. Fix the cheap ones (annotations, braces, named colour) in the same commit if it is low risk.
Then do the Breeze work from 008 (if you have not started it). Report in docs/STATUS.md only.
