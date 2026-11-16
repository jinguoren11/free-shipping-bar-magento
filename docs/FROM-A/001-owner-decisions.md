# 001 owner decisions (2026-11-16, from window A)

OWNER-APPROVED (his words, relayed): approval A = option 2: the "More tools" panel is OFF by default in the Marketplace build ("no-promotion build") and ON by default in the GitHub/download build. EU trader info (listing form only, not in code): name GetDropFlow (operator JinGuo Ren), email support@getdropflow.com, address as already in docs/STATUS.md.

Iron rule: every plugin must be one-click upgradable (first install manual, then every new version one click) for the GitHub/download build: settings upgrade card, backup, automatic rollback on failure, reuse the GetDropFlow approach (read-only repo jinguoren11/dropshipkit, Admin/Model/Upgrade). Latest-version info comes from GitHub Releases (public API, cached). Marketplace build: no self-download code (Marketplace rule); upgrades come through Adobe's mechanism; say so in the user guide. Both builds from one codebase with a channel switch.

Distribution: main download = GitHub Releases zip. The website download button links DIRECTLY to the GitHub release asset URL (browser downloads straight, no redirect page, nothing stored on the website). README + Release notes + admin settings footer promote GetDropFlow (getdropflow.com) and ask for a GitHub star (admin settings footer link only in the download build). Repo stays PRIVATE until the owner approves making it public; do not change visibility, and do not put live star/download links before then.

Add to the test list: one-click upgrade success and failure-with-rollback tests (ASUS/Mac will run them when you ask in docs/TEST-REQUESTS.md).

# Reporting rule
Report ONLY in docs/STATUS.md (+ TEST-REQUESTS / TEST-REPORTS). Do not send the owner reports, screenshots or tables in chat: one short line at most per step. Approval requests go in STATUS.md "需要老任批准"; window A relays them.
