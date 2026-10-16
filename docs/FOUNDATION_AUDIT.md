# Foundation Audit

**Audit date:** 2026-10-16  
**Scope:** Existing Laravel 13 REST API repository, source/configuration, migrations, tests, Docker setup, and documentation.

## 1. Existing architecture and modules

- Laravel 13 application targeting PHP 8.3+ in `composer.json`, with Sanctum 4 for token authentication and MySQL 8 in the Docker setup.
- Versioned API routes under `/api/v1`; API modules include authentication, categories, products, brands, units, taxes, warehouses, suppliers, customers, purchases, purchase details, users/roles, and permissions.
- API controllers use Form Requests and API Resources inconsistently; business logic is split between services and repositories. Repository bindings are registered in `AppServiceProvider`.
- RBAC is implemented with `User`, `Role`, and `Permission` many-to-many relationships, permission middleware on API resources, role/permission assignment services, a database seeder, and a session-token-backed web dashboard.
- Eloquent migrations provide soft deletes for several catalog models, unique keys and indexes for selected identifiers, and foreign keys for purchases and RBAC pivots.
- PHPUnit 12 and Laravel Pint are declared. The only substantive existing feature suite is `RbacAuthorizationTest`; default example tests are also present.
- Docker Compose defines an app, MySQL, and phpMyAdmin service. The app uses Laravel's development server; this is a development workflow, not a production serving stack.

## 2. Critical bugs and security risks

### Critical

- No critical issue was verified during this audit.

### High

- `.env` is ignored and is not tracked by Git. Its local contents were deliberately not inspected. The tracked `.env.example` contains a fixed application key and database credential values; the Compose file also hardcodes the MySQL root password and exposes port 3306 on the host. These are unsafe defaults and encourage reuse of committed credentials.
- The app container runs `php artisan key:generate --force` every time it starts. That invalidates encrypted data and sessions and violates safe key lifecycle expectations.
- Login and password-reset endpoints have no explicit route throttling.
- Dashboard login and registration are also unthrottled. Dashboard logout is exposed as a state-changing GET route even though the dashboard layout submits logout by POST.
- Dashboard login error logs include the submitted email and exception message, file, and line, which can put personal or query data into logs.
- Warehouse failure handling logs raw exception messages/traces and reports caught exceptions, increasing the chance that SQL bindings or request data reach application logs.
- Customer and supplier controllers catch broad `Throwable` failures and return exception messages directly to clients, potentially exposing SQL or internal implementation details.
- `app/Models/category.php` declares `App\Models\Category`, but Composer PSR-4 expects `app/Models/Category.php`. This can fail in Linux containers and production deployments.
- `PurchaseResource` reads `supplier.name`, while the Supplier model/schema expose `company_name`; purchase responses therefore report no supplier name.

## 3. Missing or incorrect model relationships

- `Product` has relationships only to `Category`, although its fillable attributes and migration also include `brand_id`, `unit_id`, `tax_id`, `warehouse_id`, `created_by`, and `updated_by`. The associated optional columns have no foreign-key constraints. Product response/service paths consequently cannot consistently eager-load these related records.
- `PurchaseDetail` imports `app\Models\Purchase` with incorrect namespace casing. The correct import is `App\Models\Purchase`.
- The `Purchase` ↔ `PurchaseDetail` and `Purchase` ↔ `Supplier`/`Warehouse` relationships exist; `PurchaseDetail` also has Product, but these areas lack dedicated relationship tests.
- Product, brand, unit, tax, warehouse, and audit-user reverse relationships are incomplete. Existing constraints elsewhere (for example, supplier creator/updater and RBAC pivots) are present and should be preserved.

## 4. Authorization and validation problems

- API resource routes are within `auth:sanctum` and protected by module/action permission middleware. RBAC behavior has regression coverage for basic role permissions, self-assignment restrictions, role editor boundaries, and dashboard access.
- Authentication endpoints are public as expected, but sensitive endpoints have no rate limiter.
- The web dashboard accepts GET logout, allowing cross-site logout requests without the CSRF-protected POST form.
- Product collection filters and pagination are passed from the request without a dedicated Form Request; several other collection endpoints similarly accept `per_page` without consistent bounds.
- Purchase resources apply request validation and route-model binding, and purchase-detail lookup is scoped through its parent purchase. No resource policies are defined; route-level permissions are the primary authorization boundary.
- Response envelopes are not uniform across modules. This is existing API behavior and should not be changed without versioning or an explicit contract decision.

## 5. Testing gaps

- No dedicated API tests were found for login/registration/logout, invalid credentials, password reset flows, category/product CRUD validation and 404 behavior, purchase relationships, or database rollback behavior.
- `phpunit.xml` sets `App_ENV` and `App_MAINTENANCE_DRIVER` rather than Laravel's conventional uppercase environment names and points the coverage source directory at `App` rather than `app`.
- The test command was attempted on the current host but Artisan could not start: installed Composer dependencies require PHP 8.4.1 or later, while the available CLI is PHP 8.3.33. No test result is claimed.

## 6. Docker and environment configuration problems

- There is no repository GitHub Actions workflow.
- Compose has no MySQL health check and the app only waits for container start, not database readiness.
- The Docker image build was attempted and failed because `pdo` is passed to `docker-php-ext-install` even though PDO is built into this PHP image and has no extension `config.m4`. The `php:8.4-cli` base image already includes `pdo_sqlite`, `sqlite3`, and `mbstring`; these should not be passed to the extension installer either.
- Docker rebuilds/install dependencies at runtime and starts the built-in development server. There is no health check for the application.
- Compose uses a bind mount for the full repository and a persistent named MySQL volume. The volume is retained.
- `.env.example` sets `APP_DEBUG=true`, a committed fixed `APP_KEY`, root database access, and sample credentials; README instructions do not distinguish local host DB settings from Compose network settings.
- Cache, database, and session name prefixes read `App_NAME` instead of the documented `APP_NAME`, so they fall back to the generic `laravel` prefix on case-sensitive environments.
- Session encryption is disabled in the environment template while the dashboard stores a Sanctum token in the session; a database-only disclosure could expose that token. Sanctum token expiration is also unset, so token lifetime needs an explicit operational policy.
- The sample `MAIL_MAILER=log` is suitable for local development but writes password-reset links into application logs; production mail delivery must use an appropriately protected provider.
- The Dockerfile includes the MySQL/PDO, GD, and ZIP extensions used by the declared environment, but copies the moving `composer:latest` image rather than a stable Composer version.

## 7. Prioritized fixes

### Critical

1. None identified in the reviewed current source/configuration. Reassess secret exposure if repository history or deployment credentials are in scope.

### High

1. Remove fixed secrets from `.env.example` and Compose, use required externally supplied credentials, and ensure Compose startup never regenerates the app key.
2. Correct Linux PSR-4 model filename casing and PurchaseDetail's namespace import.
3. Add rate limits to API and dashboard authentication/recovery endpoints, remove state-changing GET logout, and avoid logging submitted email addresses or returning raw exception details.
4. Correct the supplier field in purchase responses and add focused regression coverage.
5. Add PHP 8.4-compatible CI checks and isolate the test database.
6. Enable session encryption in the sample, allow an explicit Sanctum token lifetime, and document production mailer requirements.

### Medium

1. Add missing Product relationships and corresponding optional foreign-key constraints in a forward migration. Check pre-existing data for invalid references before deploying that migration.
2. Expand the highest-risk authentication, CRUD, validation, missing-record, relationship, and rollback tests.
3. Correct PHPUnit environment/source configuration and standardize validation for collection filters and page sizes.
4. Improve Compose health checks/startup ordering and make local-vs-container configuration clear.
5. Correct `APP_NAME` environment lookups for cache/Redis prefixes, session cookie naming, and Slack logging.

### Low

1. Align API response envelopes where an explicit API-version contract permits it.
2. Review unused repository abstractions and formatting after the correctness and security fixes.
3. Document production serving, secret management, backup, and operational readiness separately from the development Compose workflow.

## Audit constraints

- No production database was accessed, and no database or Docker volume was changed.
- `.env` is not tracked. No `.env` contents or credential values are included here.
- Changes to data-bearing schemas should be applied through forward-only migrations and preflighted against each deployment database.
