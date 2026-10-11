# AGOS

## Dual-platform disaster monitoring system

AGOS is a disaster-monitoring concept and Laravel web portal for helping local government units (LGUs), disaster risk reduction and management offices (DRRMOs), and response teams turn community flood reports into clearer operational decisions.

The project is being developed as an undergraduate thesis at Ateneo de Davao University. Its planned intelligence workflow combines crowdsourced reports, social-media signals, multilingual text processing, location context, and incident validation at the barangay level.

> **Repository:** [leyquinn/AGOS_Website](https://github.com/leyquinn/AGOS_Website)

## Current status

The repository currently contains the Laravel web application shell and the initial AGOS portal experience:

- Public landing page describing the AGOS workflow and goals.
- Login screen and portal navigation.
- Dashboard, operations, reports, harvesting, analytics, and settings screens.
- Tailwind CSS styling built through Vite.
- Laravel Sanctum installed, with the default authenticated `/api/user` endpoint.
- Laravel’s default User, cache, and jobs database migrations.
- Example Unit and Feature test suites.

Several portal values and health indicators are currently static UI data. The production data pipeline, social-media connectors, trained NLP service, clustering service, mobile client, and responder integrations should be treated as planned work unless they have been added separately.

## Project goals

AGOS is designed around four related goals:

1. **Collect signals** from citizen reports and supported social platforms.
2. **Validate content** by identifying relevant flood reports, severity, language, and location information.
3. **Group incidents** spatially so that activity can be viewed by barangay and geographic area.
4. **Support response decisions** with a dashboard that presents confidence, affected areas, source health, and operational status.

The planned research architecture uses:

- **Multi-task XLM-RoBERTa** for multilingual and code-switched text classification.
- **K-Means++ clustering** for grouping validated reports by location.
- **REST/JSON services** for communication with a future mobile application.

These model and integration components are part of the project direction; they are not currently exposed as working services by the Laravel routes in this repository.

## Technology stack

| Area | Technology |
| --- | --- |
| Backend | PHP 8.2+, Laravel 12 |
| Authentication/API foundation | Laravel Sanctum 4 |
| Server-rendered UI | Blade components |
| Styling | Tailwind CSS 4 |
| Frontend tooling | Vite 7, Laravel Vite Plugin |
| Database (local default) | SQLite |
| Database (supported by Laravel) | MySQL and other Laravel-supported drivers |
| Testing | PHPUnit 11 through `php artisan test` |
| Planned intelligence layer | Python, XLM-RoBERTa, K-Means++ |

## Requirements

Install the following before setting up the project:

- PHP 8.2 or newer
- Composer
- Node.js and npm
- A database supported by Laravel (SQLite is the simplest local option)
- Git

On Windows, PHP and Composer can be installed through tools such as [Laravel Herd](https://herd.laravel.com/) or another PHP distribution. Confirm that `php`, `composer`, `node`, and `npm` are available on your `PATH`.

## Installation

Clone the repository and enter the project directory:

```bash
git clone https://github.com/leyquinn/AGOS_Website.git
cd AGOS_Website
```

Install PHP dependencies, create the environment file, generate an application key, migrate the database, and build frontend assets:

```bash
composer setup
```

The `composer setup` script runs the following steps:

1. `composer install`
2. Copies `.env.example` to `.env` when `.env` does not exist
3. Generates `APP_KEY`
4. Runs database migrations
5. Installs npm dependencies
6. Builds production frontend assets

### Database configuration

The example environment is configured for SQLite:

```dotenv
DB_CONNECTION=sqlite
```

The repository includes `database/database.sqlite`. If you create a new local SQLite database, ensure that the file exists before migrating:

```bash
php artisan migrate
```

To use MySQL instead, update the database values in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=agos
DB_USERNAME=root
DB_PASSWORD=
```

Create the database in MySQL first, then run:

```bash
php artisan migrate
```

Never commit `.env` or production credentials. Use `.env.example` as the template for new environments.

## Running the application

### Development mode

Run the Laravel server, queue listener, log viewer, and Vite development server together:

```bash
composer dev
```

Then open [http://localhost:8000](http://localhost:8000).

If you prefer separate processes, use:

```bash
php artisan serve
npm run dev
```

The Vite development server provides hot module replacement for frontend changes. Keep it running while editing files in `resources/`.

### Production asset build

Build optimized frontend assets with:

```bash
npm run build
```

For a deployment, also configure the application environment, generate a production key, migrate using `--force`, and apply the normal Laravel cache and web-server configuration for the target host.

## Available pages and routes

The current web routes are defined in [`routes/web.php`](routes/web.php):

| URL | Route name | Purpose |
| --- | --- | --- |
| `/` | `landing` | Public AGOS overview |
| `/login` | `login` | Login screen |
| `/dashboard` | `dashboard` | Flood-intelligence overview |
| `/operations` | `operations` | Operations and field-unit view |
| `/reports` | `reports` | Report review view |
| `/harvesting` | `harvesting` | Source and harvesting view |
| `/analytics` | `analytics` | Analytics and model view |
| `/settings` | `settings` | Portal settings view |

The API route file currently exposes:

```http
GET /api/user
```

It requires a valid Sanctum-authenticated request. Additional report, incident, map, and mobile endpoints should be added as the API contract is implemented.

## Project structure

```text
agos/
├── app/
│   ├── Http/
│   │   └── Controllers/       # Application controllers
│   ├── Models/                # Eloquent models
│   └── Providers/             # Application service providers
├── database/
│   ├── factories/             # Model factories
│   ├── migrations/            # Database schema migrations
│   └── seeders/               # Database seeders
├── public/
│   └── images/                # Public images and branding
├── resources/
│   ├── css/                   # Tailwind entry styles
│   ├── js/                    # Vite JavaScript entry points
│   └── views/
│       ├── auth/              # Authentication views
│       ├── components/        # Reusable Blade components
│       ├── analytics/         # Analytics page
│       ├── harvesting/        # Harvesting page
│       ├── operations/        # Operations page
│       ├── reports/           # Reports page
│       ├── settings/          # Settings page
│       └── landing.blade.php  # Public landing page
├── routes/
│   ├── api.php                # API routes
│   ├── console.php            # Artisan commands
│   └── web.php                # Browser routes
├── tests/
│   ├── Feature/               # HTTP and integration-level tests
│   └── Unit/                  # Unit tests
├── composer.json
├── package.json
└── vite.config.js
```

## Development workflow

### Adding a page

1. Add the Blade view under the appropriate directory in `resources/views/`.
2. Reuse the existing layout and shared components in `resources/views/components/`.
3. Register the route in `routes/web.php`.
4. Add a Feature test for the route and its expected response.

### Adding application data

1. Create a migration with `php artisan make:migration`.
2. Add or update an Eloquent model in `app/Models/`.
3. Add factories and seeders when repeatable development data is needed.
4. Validate requests and keep API response shapes consistent.
5. Cover the behavior with Unit or Feature tests.

### Adding API functionality

Keep API routes in `routes/api.php`, protect private resources with Sanctum middleware, and use JSON responses or API Resources for stable client-facing output. Treat the API as a separate contract because it is intended to support a future mobile application as well as other clients.

## Testing

Run the full Laravel test suite with:

```bash
php artisan test
```

Or use the Composer shortcut:

```bash
composer test
```

Tests use an in-memory SQLite database and isolated test environment values configured in [`phpunit.xml`](phpunit.xml).

## Useful Artisan commands

```bash
php artisan route:list       # List registered routes
php artisan migrate:status   # Show migration status
php artisan migrate          # Apply pending migrations
php artisan migrate:fresh    # Recreate the local database
php artisan config:clear     # Clear cached configuration
php artisan view:clear       # Clear compiled Blade views
php artisan optimize:clear   # Clear Laravel caches
```

Use `migrate:fresh` only when resetting a disposable local database; it deletes existing database tables.

## Configuration reference

Important values in `.env` include:

| Variable | Purpose |
| --- | --- |
| `APP_NAME` | Display name for the application |
| `APP_ENV` | Environment name, such as `local` or `production` |
| `APP_KEY` | Encryption key generated by Laravel |
| `APP_DEBUG` | Detailed error output; disable in production |
| `APP_URL` | Canonical application URL |
| `DB_*` | Database connection settings |
| `SESSION_*` | Session driver and lifetime |
| `QUEUE_CONNECTION` | Queue backend for background work |
| `CACHE_STORE` | Cache backend |
| `MAIL_*` | Mail transport settings |
| `VITE_APP_NAME` | Frontend-visible application name |

External social-media credentials, model-service URLs, map providers, and notification providers are not currently defined in `.env.example`. Add them only with a documented integration and keep secrets outside source control.

## Roadmap

Planned work includes:

- Replace static portal metrics with persisted report and incident data.
- Add role-aware authentication and authorization for LGU, analyst, dispatcher, and field users.
- Define report, incident, barangay, source, validation, and location data models.
- Implement report submission and moderation APIs for the mobile client.
- Add ingestion adapters with rate limits, retries, provenance, and provider-compliant data handling.
- Connect a Python NLP service for relevance, severity, language, and location extraction.
- Implement validated-event clustering and map-based visualization.
- Add queue-backed processing, observability, notifications, and audit history.
- Add representative seed data and automated coverage for critical workflows.

## Contributing

1. Create a focused branch from the current development branch.
2. Make a small, documented change that follows the existing Laravel and Blade conventions.
3. Run `php artisan test` and `npm run build`.
4. Update this README when setup or user-facing behavior changes.
5. Open a pull request describing the problem, implementation, and validation performed.

Do not commit `.env`, credentials, API tokens, generated logs, or production data.

## License and academic use

This repository is associated with an undergraduate thesis project. Confirm the project’s intended license and any data, model, or social-platform usage restrictions with the project maintainers before redistributing code, datasets, trained models, or collected reports.
