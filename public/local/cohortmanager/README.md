# Cohort Manager for Moodle

**A unified, reactive interface for managing everything about Moodle cohorts — in a single plugin.**

Moodle ships cohort management scattered across multiple screens: one page to create cohorts, another to assign members, a separate admin section for cohort sync enrolments, and yet another for cohort-based role assignments (via `tool_cohortroles`). **Cohort Manager** brings all of these workflows into one modern, fast, single-page application built with Vue 3 + TypeScript + Pinia and served directly inside Moodle.

## Why Cohort Manager?

| Without this plugin | With Cohort Manager |
|---|---|
| Cohort CRUD lives under **Site administration > Users > Cohorts** | Full CRUD with search, pagination, and instant navigation — one click from the Moodle nav menu |
| Adding members means navigating away from the cohort page | Add/remove members with a searchable modal, inline, without losing context |
| Cohort sync enrolments are buried per-course under **Enrolment methods** | View, create, edit, delete, and toggle enrol instances for **all courses** from a single tab |
| `tool_cohortroles` role assignments live in a separate admin page | Manage role assignments with sync status visible directly on the cohort detail view |
| Custom fields require visiting the core custom fields manager | Access custom fields management from the plugin's own navigation |
| No role management for user-context roles tied to cohorts | Create, edit, and delete user-context roles from within the plugin |

## Key Features

### Cohort Management (Full CRUD)
- **List** all cohorts with real-time search and server-side pagination
- **Create** cohorts with name, ID number, description (TinyMCE rich-text editor), visibility toggle, context/category selector, and optional theme assignment
- **Edit** any cohort inline with pre-populated forms
- **Delete** cohorts with confirmation dialogs
- **Cohort Portrait** — at-a-glance dashboard showing member count, enrol instance count, total enrolments, and role assignment count

### Member Management
- **Add members** via a searchable user-picker modal
- **Remove members** individually or in bulk with confirmation
- **Dynamic members table** with sortable columns (name, username, email, city, country), configurable column visibility, and pagination

### Cohort Sync Enrol Instances
- **View** all enrol instances linked to a cohort across every course — with course name, role, status, enrolled user count, group, and group member count
- **Create** enrol instances in multiple courses at once: search and select courses, pick a role, and optionally assign to a group (or auto-create a new one)
- **Edit** existing enrol instances (change role, group)
- **Toggle** enrol instance status (active/inactive) with one click
- **Delete** enrol instances with warnings about affected user enrolments
- **Filter** enrol instances by status (all / active / inactive) and search by course name

### Cohort Role Assignments (`tool_cohortroles` Integration)
- **List** all role assignments for a cohort with user info, role name, and assignment count
- **Assign** users to cohort roles with a searchable user/role picker
- **Delete** role assignments (takes effect after next scheduled sync)
- **Sync status** — last and next sync times displayed with a note about background processing

### Roles Management
- **List** all user-context assignable roles with search and pagination
- **Create** new roles scoped to user context
- **Edit** role descriptions
- **Delete** roles (with system-role protection)

### Custom Fields
- Direct access to Moodle's custom field manager for cohorts, embedded within the plugin's navigation

### Cohort Themes
- When `Allow cohort themes` is enabled, a theme selector appears on cohort create/edit forms

## Architecture

Cohort Manager is a **progressive SPA** that boots inside Moodle's standard page layout:

```
index.php → Moodle page shell → Vue 3 app mounts on #cohort-manager-app
```

- **Frontend**: Vue 3.5 + TypeScript + Pinia + Vue Router (HTML5 History mode), built with Vite 7
- **Backend**: 30+ Moodle Web Service APIs (AJAX) exposed through `db/services.php`
- **Integration points**: `core_cohort` APIs, `enrol_cohort` plugin, `tool_cohortroles` API, `core_customfield`
- **State management**: Pinia stores for app configuration and i18n strings (loaded from Moodle's language system)
- **Transitions**: Smooth fade transitions between routes

## Requirements

- **Moodle 4.5** (supported: `[405, 405]`)
- **PHP 8.1+**
- PHP extensions: `ext-zip`, `ext-zlib`
- Required plugins:
  - `enrol_cohort` (ships with Moodle core)
  - `tool_cohortroles` (ships with Moodle core)

## Installation

```bash
cd /path/to/moodle/local/
git clone <repository-url> cohortmanager
```

Then visit Moodle's notifications page to complete the installation.

After installation, the plugin appears under **Site administration > Users > Cohort Manager** and in the navigation under **Accounts**.

## Building the Frontend

The Vue application source lives in `app/` and builds to `amd/build/`:

```bash
cd app/
npm install
npm run build
```

For development:

```bash
npm run dev
```

## Web Server Configuration

Because the app uses Vue Router with HTML5 History mode, the web server must route non-file requests to `index.php`.

### Apache

A `.htaccess` file is included at the plugin root with the required rewrite rules. No additional configuration is needed when `mod_rewrite` is enabled.

### Nginx

Add the following to your server block:

```nginx
location /local/cohortmanager/ {
    try_files $uri $uri/ /local/cohortmanager/index.php?$query_string;

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}

location /local/cohortmanager/index.php {
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

## Routes

| Route | Description |
|---|---|
| `/local/cohortmanager/` | Cohort list with search and pagination |
| `/local/cohortmanager/cohort/{id}` | Cohort detail — tabs for Details, Members, Enrol Instances, Role Assignments |
| `/local/cohortmanager/cohort/{id}/edit` | Edit cohort |
| `/local/cohortmanager/cohort/create` | Create new cohort |
| `/local/cohortmanager/custom-fields` | Custom fields manager |
| `/local/cohortmanager/roles` | User-context roles management |

## Multilingual Support

Ships with language packs for **13 languages**: Arabic, German, English, Spanish, French, Hindi, Italian, Korean, Dutch, Polish, Brazilian Portuguese, Russian, and Simplified Chinese.

Strings are loaded dynamically from Moodle's language system via a dedicated Web Service API, so any Moodle-supported language works automatically.

## Capabilities

The plugin respects standard Moodle capabilities:

- `moodle/cohort:manage` — create, edit, delete cohorts
- `moodle/cohort:assign` — add/remove members
- `moodle/cohort:view` — view cohorts and members
- `enrol/cohort:config` — manage cohort sync enrol instances
- `moodle/role:manage` — manage roles and cohort role assignments

## License

GNU GPL v3 or later — same as Moodle core.

## Credits

- **Author**: Davison Almeida
- **Copyright**: 2025 Davison Almeida <ramosdealmeidasistemas@gmail.com>
