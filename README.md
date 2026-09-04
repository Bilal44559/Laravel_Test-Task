# Localization Management Service

API-driven Laravel service for managing multi-language localizations with labels, contextual metadata, lifecycle status, versioning, search, and fast JSON export for frontend apps (e.g. Vue.js).

Built as a senior developer coding exercise focused on clean architecture, performance, and security.

## Features

- Multiple languages (`en`, `fr`, `es`) with the ability to add more
- Contextual labels (`mobile`, `desktop`, `web`)
- Metadata attributes on localizations: `status` (`draft`, `approved`, `archived`), `context` notes, auto-incrementing `version`, and `created_by_user_id` tracking
- Advanced Database Indexing Techniques applied for high concurrency & performance
- Create / update / view / search localizations by key, content, language, status, or labels
- JSON export endpoint returning localizations with metadata (or flat `key => content` map)
- Token-based API authentication
- Domain seeders (`LanguageSeeder`, `LabelSeeder`, `LocalizationSeeder`) & Bulk seed command for 100k+ records
- OpenAPI documentation (`openapi.yaml`)
- Docker support

## Requirements

- PHP 8.3+
- Composer
- SQLite (default) or MySQL 8+
- Optional: Docker

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Default seeded user:

- Email: `admin@example.com`
- Password: `password`

## Authentication

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

Use the returned token:

```bash
Authorization: Bearer {token}
```

## Main endpoints

| Method | Endpoint | Route Name | Description |
|--------|----------|------------|-------------|
| POST | `/api/login` | `auth.login` | Get API token |
| POST | `/api/logout` | `auth.logout` | Revoke current token |
| GET | `/api/user` | `auth.user` | Get authenticated user profile |
| GET | `/api/languages` | `languages.index` | List languages |
| POST | `/api/languages` | `languages.store` | Add language |
| GET | `/api/labels` | `labels.index` | List labels |
| POST | `/api/labels` | `labels.store` | Add label |
| GET | `/api/localizations` | `localizations.index` | Search/list localizations |
| GET | `/api/localizations/search` | `localizations.search` | Dedicated search (`q`, key, content, language, status, labels) |
| POST | `/api/localizations` | `localizations.store` | Create/upsert localization |
| GET | `/api/localizations/{id}` | `localizations.show` | View single localization |
| PUT | `/api/localizations/{id}` | `localizations.update` | Update localization |
| DELETE | `/api/localizations/{id}` | `localizations.destroy` | Delete localization |
| GET | `/api/localizations/export?language=en` | `localizations.export` | Fast JSON export |

### Create localization with metadata

```bash
curl -X POST http://127.0.0.1:8000/api/localizations \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "key": "home.welcome",
    "language": "en",
    "content": "Welcome",
    "status": "approved",
    "context": "Homepage main hero section greeting",
    "labels": ["web", "mobile"]
  }'
```

### Search localizations

```bash
curl "http://127.0.0.1:8000/api/localizations/search?q=welcome&language=en&status=approved&labels[]=web" \
  -H "Authorization: Bearer TOKEN"
```

Filters: `q` (key or content), `key`, `content`, `language`, `status` (`draft`, `approved`, `archived`), `labels[]`, `per_page`.

### Export for frontend

```bash
curl "http://127.0.0.1:8000/api/localizations/export?language=en&labels[]=web" \
  -H "Authorization: Bearer TOKEN"
```

Example response:

```json
{
  "language": "en",
  "localizations": {
    "home.welcome": {
      "content": "Welcome",
      "status": "approved",
      "context": "Homepage main hero section greeting",
      "version": 1
    }
  }
}
```

Optional flat string map (`include_metadata=0`):

```json
{
  "language": "en",
  "localizations": {
    "home.welcome": "Welcome"
  }
}
```

## Database Indexing Techniques

The database schema utilizes advanced indexing strategies to ensure maximum throughput and sub-millisecond query execution:

1. **Covering Composite Index (`idx_localizations_lang_status_key`)**:
   - Columns: `(language_id, status, localization_key_id)` on `localizations`
   - Purpose: Satisfies hot export queries directly from index B-Tree pages without table data lookups (Index-Only Scan).
2. **Reverse Composite Pivot Index (`idx_pivot_label_key`)**:
   - Columns: `(label_id, localization_key_id)` on `label_localization_key`
   - Purpose: Accelerates tag/label filtering (`WHERE IN (labels)`).
3. **Status & Date Sorting Index (`idx_localizations_status_created`)**:
   - Columns: `(status, created_at)` on `localizations`
   - Purpose: Eliminates filesort overhead for status-filtered queries ordered by timestamp/ID.
4. **Active Language Index (`idx_languages_active_code`)**:
   - Columns: `(is_active, code)` on `languages`
   - Purpose: Instant lookups for active languages (`WHERE is_active = true AND code = ?`).

## Bulk seeding (scalability)

```bash
php artisan localizations:seed-bulk --count=100000
```

## Tests

```bash
php artisan test
```

Includes unit, feature, and a performance smoke test for the export endpoint (37 passing tests).

## Docker

```bash
docker compose up --build
```

App: http://127.0.0.1:8000

## Design choices

1. **Normalized & Enhanced Schema** — `languages`, `localization_keys`, `localizations` (with `status`, `context`, `version`, `created_by_user_id`), `labels`, and pivot table.
2. **SOLID Services & Clean Controllers** — Controllers stay thin; `LocalizationService` manages write/search/versioning logic; `LocalizationExportService` owns the high-performance export path.
3. **Route Grouping** — Clean route architecture in `routes/api.php` grouped by `Route::prefix(...)`, `Route::name(...)`, and `Route::controller(...)`.
4. **Export Performance & Indexing** — Covering indexes and single join query selecting key, content, and metadata. Short versioned cache (30s) invalidated on writes so responses stay fresh.
5. **Token Auth without Heavy CRUD Packages** — Custom bearer token auth (Sanctum-compatible shape) for lightweight security.
6. **PSR-12 & Formatting** — Verified with Laravel Pint (`vendor/bin/pint`).

## API docs

See [`openapi.yaml`](openapi.yaml) for the full OpenAPI 3 specification.
