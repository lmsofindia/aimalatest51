# Creating the "HR Manager" role

The HR Manager role lets a non-admin user open every report in the Reports Hub with
**full, all-user access** — the same experience an admin gets: selection forms, the
user search, and data across all users.

The single switch that grants this is the capability **`local/reportpanel:viewall`**.
Everyone else (any logged-in user) already has `local/reportpanel:view` from the
authenticated-user archetype, so they get the self-service (own-data) view with no
extra setup.

---

## Step 1 — Create the role

Site administration → **Users → Permissions → Define roles** → **Add a new role**.

1. Skip the "Use role or archetype" preset (choose *No role*) → **Continue**.
2. Short name: `hrmanager`
3. Custom full name: `HR Manager`
4. Description: *Can view all reports in the Reports Hub across all users.*
5. **Context types where this role may be assigned**: tick **System** (and **Category**
   if you ever want category-scoped HR managers).
6. Leave archetype as *None* (or *Manager* if you want it to inherit manager-style
   defaults — see note below).

## Step 2 — Allow the capabilities

In the capability filter box at the bottom of the role form, add **Allow** for:

| Capability | Why | Required |
|---|---|---|
| `local/reportpanel:view` | See the panel + own reports | Yes |
| `local/reportpanel:viewall` | **Full mode** — all-user data, pickers, user search | Yes |

That alone makes all 8 cards appear and switches the three custom pages (quiz,
certificate, consolidated) into full mode.

### Capabilities the *linked* reports also need

Cards 2, 3 and 4 open existing pages that enforce their **own** permissions. The panel
**hides a card unless the viewer holds the capability that card's page requires**, so
if you leave these *Not set*, the Site reports / Log report / Team reports cards simply
won't appear (no more "access denied" dead-ends). Allow them to both reveal **and**
enable the card:

| Capability | Needed to open | Notes |
|---|---|---|
| `moodle/site:viewreports` | Site reports, Log report | Site-wide reporting access |
| `report/log:view` | Log report | The log report page |
| `mod/quiz:viewreports` | The quiz overview report (`/mod/quiz/report.php`) | Per the linked teacher report |
| `moodle/grade:viewall` | Course/quiz grades shown in reports | Read all users' grades |
| `moodle/course:viewparticipants` | User lists / participants | Helpful for cross-course views |
| `local/edzteams:viewall` | Team reports (all teams) | Only if `local_edzteams` is installed |
| `moodle/category:viewcourselist` | Category → course pickers | Usually already allowed |

> `mod/quiz:viewreports`, `moodle/grade:viewall` and `moodle/course:viewparticipants`
> are normally **course-level** capabilities. Granting them in a **System**-context
> role makes them apply site-wide, which is what an HR Manager needs. If you prefer to
> limit HR Managers to specific course categories, create the role at **Category**
> context and assign it on those categories instead.

Click **Create this role**.

## Step 3 — Assign people to the role

Site administration → **Users → Permissions → Assign system roles** → **HR Manager** →
move the relevant users from *Potential users* to *Existing users*.

(If you made it a category role: go to the category → **Assign roles** → HR Manager.)

## Step 4 — Verify

Log in as an HR Manager (or use *Log in as*) and open `/local/reportpanel/index.php`:

- [ ] All 8 cards are visible.
- [ ] Quiz reports shows the category → course pickers and a quiz stats table.
- [ ] Certificate reports shows pickers and issued counts.
- [ ] Consolidated report shows the user search; picking a user builds the full table
      and the PDF/CSV downloads work.
- [ ] Site reports / Log report / Team reports open without "permission denied".

---

## Quick reference — who sees what

| Capability held | Experience |
|---|---|
| `local/reportpanel:viewall` (admin, HR Manager) | Full mode: all 8 cards, all-user data, pickers + search |
| `local/reportpanel:view` only (any logged-in user) | Self mode: own quiz/cert/consolidated data; site-wide cards hidden |
| `local/edzteams:viewteam` (team manager, no viewall) | Self mode **plus** the Team reports card |

## Notes

- You do **not** need to touch admins — they inherit `local/reportpanel:viewall` from
  the `manager` archetype and always see everything.
- Removing `local/reportpanel:viewall` from a user instantly drops them back to the
  self-service (own-data) view; no other change needed.
- The plugin writes no data and stores nothing about users, so assigning/ை removing
  the role has no data-cleanup implications.

## Card-to-capability map (summary)

1. Leaderboard — opens block_xp; no extra cap beyond viewing block_xp.
2. Site reports — `moodle/site:viewreports`.
3. Log report — `report/log:view` (+ `moodle/site:viewreports`).
4. Team reports — `local/edzteams:viewall` (or `:viewteam` for own team).
5. Quiz reports — `local/reportpanel:viewall` for the panel page; `mod/quiz:viewreports` to open the linked quiz report.
6. Certificate reports — `local/reportpanel:viewall`; the linked customcert view needs `mod/customcert:view`.
7. Consolidated user report — `local/reportpanel:viewall`; reads grades/badges/completion for the chosen user.
8. Badges & achievements — opens core badges page.
