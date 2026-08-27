# Audio/Video Schedule Generator

A lightweight Laravel + Inertia + Vue application that generates a monthly audio/video support schedule for a local Jehovah's Witness congregation.

Congregation data lives in **Google Sheets**, not in a SQL database. Runtime data is not stored in this repository.

## Requirements

- Docker
- Docker Compose

Local PHP and Node.js are **not** required. The `app` container includes PHP 8.3, Composer, Node.js 20, and npm.

## First-time setup

```bash
git clone <repository-url>
cd AV-meeting-assignment
cp .env.example .env
docker compose up -d
```

The first start copies `.env` if needed, installs PHP and Node dependencies, generates `APP_KEY`, builds frontend assets, and serves the app.

Open:

```text
http://localhost:8000
```

`/` redirects to `/schedule`.

## Google Sheets setup

1. Create a Google Cloud project.
2. Enable the **Google Sheets API**.
3. Create a **service account** and download its JSON key.
4. Create a Google Spreadsheet with tabs named:
   - `Settings`
   - `Brothers`
   - `Meetings`
   - `Schedule`
5. Share the spreadsheet with the service account email (`...@....iam.gserviceaccount.com`) as **Editor**.
6. Configure environment variables in `.env`:

```env
GOOGLE_SHEETS_SPREADSHEET_ID=your-spreadsheet-id
GOOGLE_SERVICE_ACCOUNT_JSON=/path/to/service-account.json
```

`GOOGLE_SERVICE_ACCOUNT_JSON` may also contain the full JSON key as a single-line string (useful on Render).

7. Initialize headers and the default settings row:

```bash
docker compose exec app php artisan sheets:setup
```

The setup command creates missing tabs, writes headers, and seeds the default settings row when the Settings tab is empty. It does not overwrite existing data rows.

## Commands

| Task | Command |
|---|---|
| Start | `docker compose up -d` |
| Stop | `docker compose down` |
| Logs | `docker compose logs` |
| Shell | `docker compose exec app bash` |
| Tests | `docker compose exec app php artisan test` |
| Initialize sheets | `docker compose exec app php artisan sheets:setup` |
| Clear caches | `docker compose exec app php artisan optimize:clear` |
| Vite dev (HMR) | `docker compose exec app npm run dev` |
| Production assets | `docker compose exec app npm run build` |

Vite HMR uses port `5173` when `npm run dev` is running. The default `docker compose up` flow serves pre-built assets from `public/build`.

## Health check

```text
GET /health
```

Returns `{"status":"ok"}`.

## Stack

- PHP 8.3
- Laravel 11
- Inertia.js
- Vue 3
- Tailwind CSS
- Vite
- TypeScript
- Pest
- Google Sheets API (`google/apiclient`)
- Docker (single `app` service, no database container)

## Architecture (Phase 2)

```text
Controllers
    ↓
Repositories
    ↓
SpreadsheetStorageInterface
    ↓
GoogleSheetsService
    ↓
Google Spreadsheet
```

Tests use `Tests\Fakes\FakeSpreadsheetStorage` so the suite does not call the live Google API.

## What is not implemented yet

Later phases add brothers and meetings CRUD UI, the scheduling engine, PDF export, and Render deployment.

## Notes

- Do not commit `.env`, `service-account*.json`, or production spreadsheet IDs.
- Laravel 11 is required by the project specification. Composer 2.10 blocks some `laravel/framework` 11.x releases because of published security advisories. This project ignores advisories for `laravel/framework` only so the specified version can be installed. Review `composer audit` before any production deployment.
