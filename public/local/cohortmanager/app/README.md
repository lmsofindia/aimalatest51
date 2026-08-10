# Cohort Manager

A Progressive Vue interface for managing Moodle cohorts with full CRUD operations, pagination, and responsive design.

## Features

- **Cohort Listing**: Display all cohorts with pagination
- **Search**: Search cohorts by name or ID
- **CRUD Operations**: 
  - Create new cohorts
  - View cohort details
  - Edit existing cohorts
  - Delete cohorts
- **Responsive Design**: Works on desktop and mobile devices
- **Routing**: Direct access to internal pages via URL

## Project Structure

```
local/cohortmanager/app/
├── src/
│   ├── App.vue                 # Main application component
│   ├── CohortDetail.vue        # Cohort details view
│   ├── CohortEdit.vue          # Cohort edit form
│   ├── CohortCreate.vue        # Cohort create form
│   ├── main.ts                 # Application entry point
│   ├── router/
│   │   └── index.ts            # Vue Router configuration
│   └── utils/
│       └── moodle.ts           # Moodle web service integration
├── index.html                  # HTML entry point
├── package.json                # Project dependencies
├── vite.config.ts             # Vite build configuration
└── README.md                   # This file
```

## Installation

1. Navigate to the project directory:
```bash
cd local/cohortmanager/app
```

2. Install dependencies:
```bash
npm install
```

3. Start the development server:
```bash
npm run dev
```

## Usage

### Main Features

1. **View Cohorts**: The main page displays all cohorts in a table format with pagination
2. **Search Cohorts**: Use the search bar to filter cohorts by name or ID
3. **Create Cohort**: Click "New Cohort" button to create a new cohort
4. **View Cohort Details**: Click on a cohort name to view detailed information
5. **Edit Cohort**: Click the edit icon to modify an existing cohort
6. **Delete Cohort**: Click the delete icon to remove a cohort (with confirmation)

### Direct URL Access

The application supports direct access to specific pages:

- **Cohort List**: `/`
- **Cohort Details**: `/cohort/{id}`
- **Edit Cohort**: `/cohort/{id}/edit`
- **Create Cohort**: `/cohort/create`

## Integration with Moodle

This application integrates with Moodle web services defined in:

- `cohort/externallib.php` - External functions for cohort management
- `lib/db/services.php` - Web service definitions

The following Moodle web services are used:

- `core_cohort_search_cohorts` - Search cohorts with pagination
- `core_cohort_get_cohorts` - Get cohort details
- `core_cohort_get_cohort_members` - Get cohort members
- `core_cohort_create_cohorts` - Create new cohorts
- `core_cohort_update_cohorts` - Update existing cohorts
- `core_cohort_delete_cohorts` - Delete cohorts

## Building for Production

To build the application for production:

```bash
npm run build
```

The built files will be in the `dist/` directory.

## Technologies Used

- **Vue 3**: Progressive JavaScript framework
- **Vue Router**: Client-side routing
- **TypeScript**: Type-safe JavaScript
- **Vite**: Fast build tool and development server
- **SCSS**: CSS preprocessor for styling

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## License

This project is part of the Moodle ecosystem and follows the same licensing terms.
