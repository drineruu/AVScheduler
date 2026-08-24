# Audio/Video Schedule Generator

A lightweight Laravel + Inertia + Vue application that will generate a monthly audio/video support schedule for a local Jehovah's Witness congregation.

Congregation data will live in **Google Sheets**, not in a SQL database. Persistence is not wired up yet (Phase 2). Runtime data will not be stored in this repository.

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

## Commands

| Task | Command |
|---|---|
| Start | `docker compose up -d` |
| Stop | `docker compose down` |
| Logs | `docker compose logs` |
| Shell | `docker compose exec app bash` |
| Tests | `docker compose exec app php artisan test` |
| Clear caches | `docker compose exec app php artisan optimize:clear` |
| Vite dev (HMR) | `docker compose exec app npm run dev` |
| Production assets | `docker compose exec app npm run build` |

Vite HMR uses port `5173` when `npm run dev` is running. The default `docker compose up` flow serves pre-built assets from `public/build`.

## Health check

```text
GET /health
```

Returns `{"status":"ok"}`.

## Stack (Phase 1)

- PHP 8.3
- Laravel 11
- Inertia.js
- Vue 3
- Tailwind CSS
- Vite
- TypeScript
- Pest
- Docker (single `app` service, no database container)

## What is not implemented yet

Later phases add Google Sheets storage, brothers and meetings CRUD, the scheduling engine, PDF export, and Render deployment.

## Notes

Laravel 11 is required by the project specification. Composer 2.10 blocks some `laravel/framework` 11.x releases because of published security advisories. This project ignores advisories for `laravel/framework` only so the specified version can be installed. Review `composer audit` before any production deployment.
