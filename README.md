<p align="center">
  <img src="assets/banner.svg" alt="Inventory Management REST API Banner" width="100%">
</p>

# Inventory Management REST API

A Laravel 13 REST API with versioned `/api/v1` routes, Sanctum token authentication, role/permission-based authorization, Eloquent models, Form Requests, API Resources, services, and repositories.

Implemented modules include authentication, roles and permissions, categories, products, brands, units, taxes, warehouses, suppliers, customers, purchases, and purchase-detail reads. Stock management and sales are not implemented; see [ROADMAP.md](./ROADMAP.md).

## Prerequisites

- PHP **8.4.1 or newer** (below PHP 9.0), with Composer 2.
- MySQL 8 if running the API outside Docker.
- Docker Desktop with Docker Compose v2 if using the container workflow.
- Node.js/npm only if building the optional Vite assets.

The project is pinned to Laravel 13 and PHPUnit 12 in `composer.lock`. The Docker image supplies GD, MySQL/PDO, ZIP, and SQLite support needed by the app and tests.

## Local setup

1. Create a local environment file if one does not already exist:

   ```powershell
   if (-not (Test-Path .env)) { Copy-Item .env.example .env }
   ```

   On macOS/Linux, use `if [ ! -f .env ]; then cp .env.example .env; fi`. Set `APP_URL`, `DB_HOST=127.0.0.1`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` for your local MySQL instance. Never commit `.env`.

2. Install PHP and Composer dependencies:

   ```sh
   composer install
   ```

3. Generate an application key **only for a new local environment with an empty `APP_KEY`**:

   ```sh
   php artisan key:generate
   ```

   Do not regenerate a key for an existing environment; doing so can invalidate encrypted data and sessions.

4. Apply migrations and seed the RBAC catalog:

   ```sh
   php artisan migrate
   php artisan db:seed
   ```

   The seeder creates or updates the roles and permission catalog; it does not promote an existing user to administrator.

5. Start the development server:

   ```sh
   php artisan serve
   ```

The API is available at `http://127.0.0.1:8000/api/v1`. For frontend assets, run `npm install` and `npm run build`.

## Docker setup

1. Copy `.env.example` to `.env` only if `.env` does not already exist:

   ```powershell
   if (-not (Test-Path .env)) { Copy-Item .env.example .env }
   ```
2. Set non-empty, locally unique values for `MYSQL_ROOT_PASSWORD` and `DB_PASSWORD` in `.env`. Keep `DB_HOST=mysql` and `DB_USERNAME=inventory_app` for the Compose network.
3. Build and start the services:

   ```sh
   docker compose up --build -d
   ```

   The app waits for MySQL's health check, installs Composer dependencies, and starts Laravel's development server. It does not generate or replace `APP_KEY` and does not run migrations automatically.

4. For a new environment only, generate the key if `APP_KEY` is empty, then migrate and seed:

   ```sh
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate
   docker compose exec app php artisan db:seed
   ```

   Do not run `key:generate` when an existing key is configured.

The API is at `http://127.0.0.1:8002`; phpMyAdmin is at `http://127.0.0.1:8083`. Both database and phpMyAdmin host ports are bound to localhost. The MySQL data uses a persistent named volume. Stopping the stack with `docker compose down` preserves it; do not use `docker compose down -v` unless you intentionally want to delete the database volume.

If reusing a database volume created by the older Compose configuration, set `MYSQL_ROOT_PASSWORD` to the password already stored in that volume. MySQL's `MYSQL_USER`/`MYSQL_PASSWORD` initialization applies only to a fresh data directory; have a DBA create/grant the configured `DB_USERNAME` on an existing database when needed. Changing Compose variables does not alter existing MySQL accounts or data.

Run commands in the app container with:

```sh
docker compose exec app php artisan route:list
docker compose exec app php artisan test
```

## Environment configuration

- `.env.example` contains names and empty credential fields, not deployable secrets.
- For Docker, `DB_HOST` must be the Compose service name `mysql`; for a local host MySQL server, use `127.0.0.1`.
- The sample environment enables session encryption. Set a valid `APP_KEY` before using encrypted sessions, and do not replace a key already in use.
- The sample uses `MAIL_MAILER=log` for local development only; configure a protected mail provider before production because password-reset links are written to logs by the log mailer.
- `APP_DEBUG=true` is intended only for local development. Disable debug output in any shared or production environment.
- Supply unique database credentials and an application key through a secret manager or a protected local `.env`; never commit those values.
- Sanctum tokens do not expire automatically by default. Set `SANCTUM_EXPIRATION` to a positive number of minutes to enforce a lifetime before exposing the API beyond a trusted environment.
- Migrations must be reviewed against the target database before deployment. The product foreign-key migration expects any pre-existing product references to point to valid related records.

## Database migrations

Review the configured connection and target database before applying migrations:

```sh
php artisan migrate:status
php artisan migrate
```

Docker equivalents use `docker compose exec app`. Do not use `migrate:fresh`, `db:wipe`, or other destructive commands against a database containing data.

## Tests and code-quality checks

The PHPUnit configuration uses an isolated in-memory SQLite database by default:

```sh
php artisan test
```

In Docker, use `docker compose exec app php artisan test`. CI runs the suite against a dedicated MySQL 8 database created for the workflow.

Validate Composer metadata and run Laravel Pint:

```sh
composer validate --strict --no-check-publish
vendor/bin/pint --test path/to/changed-file.php
```

The CI workflow runs Pint in check mode on PHP files changed by the push or pull request. A full-repository Pint check currently reports pre-existing style violations outside the changed files. No PHPStan or other static-analysis tool is currently configured.

## API authentication

Register or log in at `POST /api/v1/register` or `POST /api/v1/login`. The response includes a Sanctum token. Send it on protected API calls:

```http
Authorization: Bearer <token>
Accept: application/json
```

Protected API operations require both a valid Sanctum identity and the relevant `module.action` permission. New users receive the default read-only Viewer role when the RBAC catalog has been seeded. Assign administrator privileges only to a trusted existing user:

```sh
php artisan tinker
```

```php
$user = App\Models\User::where('email', 'trusted-admin@example.com')->firstOrFail();
$user->assignRole('admin');
```

API and dashboard login/registration are rate-limited to five attempts per minute; API password recovery and verification resend routes are limited to three attempts per minute. Logout uses POST and revokes the current token.

## Common errors and troubleshooting

- **Composer reports PHP >= 8.4.1:** switch the host runtime to PHP 8.4.1+ or run the command in the Docker app container.
- **Missing PHP extension:** run `composer check-platform-reqs`; Docker installs the extensions used by the app and tests.
- **Database connection refused:** verify the MySQL service is healthy and use `DB_HOST=mysql` inside Compose, or `127.0.0.1` from the host.
- **Compose asks for database passwords:** set non-empty `MYSQL_ROOT_PASSWORD` and `DB_PASSWORD` in `.env` before starting the stack.
- **Application key is missing:** generate one only for a new environment. Never replace a key already used to encrypt application data.
- **Migration fails on the new product constraints:** inspect orphaned product references in the target database and resolve them through an approved data migration before retrying. The application migration does not delete or rewrite those rows.
- **Port is already in use:** change `DB_FORWARD_PORT` or `PHPMYADMIN_FORWARD_PORT` in `.env`; the API host port can be changed in `docker-compose.yml`.

## Project status

The current API foundation covers authentication/RBAC and the catalog and purchase endpoints listed above. Stock engine, sales, reporting, and other roadmap modules remain future work. The Docker Compose stack is a development environment, not a production serving configuration.
