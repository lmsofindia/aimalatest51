Installation:
1. Copy the folder into moodle's local directory: local/edzworkplacerpt
2. Visit Site administration -> Notifications to install.
3. Assign the capability 'local/edzworkplacerpt:viewreport' to manager roles.
4. Create a custom profile field (Administration -> Users -> Accounts -> User profile fields) with shortname 'manageremail' (or whichever you set in settings) and populate for test users.
5. Configure plugin settings (Site administration -> Plugins -> Local plugins -> Edz Workplace Report).
6. Make sure web services are enabled if you plan to call externals from JS or external systems.

Features added:
- Server-side pagination (use the 'perpage' parameter; default 20 rows per page).
- CSV export button (exports current filter resultset).

Notes:
- The certificate table used is `customcert_issues` (customcert plugin). Adjust if different.
- For production/large sites add caching and a scheduled aggregation task for chart performance.
