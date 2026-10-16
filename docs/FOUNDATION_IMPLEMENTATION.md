# Foundation Implementation

**Date:** 2026-10-16  
**Scope:** Safe foundation corrections to the existing Laravel 13 inventory API. The pre-change findings are recorded in [FOUNDATION_AUDIT.md](./FOUNDATION_AUDIT.md).

## Summary

The audit found no verified critical vulnerability, but did find unsafe environment defaults, authentication hardening gaps, incorrect model/resource behavior, and missing regression coverage. The existing API architecture and endpoint paths were retained. No stock engine, sales module, or other roadmap features were added.

## Files changed and why

### Created

- `.dockerignore` — excludes local environment files, dependencies, databases, and logs from the Docker build context.
- `.github/workflows/ci.yml` — adds PHP 8.4 CI with Composer validation/install, an isolated MySQL 8 service, changed-file Pint checks, and the test suite.
- `database/migrations/2026_10_16_000001_add_product_foreign_keys.php` — adds nullable foreign keys for product brand, unit, tax, warehouse, and audit-user references, using `nullOnDelete`.
- `tests/Feature/ApiFoundationTest.php` — covers category validation/404, product CRUD, relationship validation/loading, purchase supplier serialization, and transaction rollback.
- `tests/Feature/AuthenticationApiTest.php` — covers successful/failed login, token response exposure, login throttling, default role assignment, forgotten-password enumeration resistance, reset token revocation, and logout revocation.
- `tests/Feature/WebAuthSecurityTest.php` — covers dashboard rate limits and rejects state-changing GET logout.

### Modified

- **Environment/build:** `.env.example`, `Dockerfile`, `docker-compose.yml`
  - Removed committed example credentials and made Compose credentials required inputs.
  - Corrected PHP extension installation, pinned the Composer major image, bound host ports to localhost, added MySQL/app health checks and readiness ordering, and preserved the named database volume.
  - Enabled session encryption in the sample and added an optional `SANCTUM_EXPIRATION` setting; token expiry remains disabled unless configured.
- **PHP and test configuration:** `composer.json`, `composer.lock`, `phpunit.xml`
  - Aligned the PHP platform requirement with installed Laravel dependencies (PHP 8.4.1+), removed key generation and migration side effects from Composer setup, and corrected PHPUnit environment/source paths.
- **Application configuration:** `config/cache.php`, `config/database.php`, `config/logging.php`, `config/sanctum.php`, `config/session.php`
  - Corrected `APP_NAME` casing in environment lookups and made Sanctum token lifetime configurable without changing its default.
- **API and dashboard:** `routes/api.php`, `routes/web.php`, `app/Http/Controllers/Api/V1/CustomerController.php`, `app/Http/Controllers/Api/V1/SupplierController.php`, `app/Http/Controllers/Api/V1/WarehouseController.php`, `app/Http/Controllers/Web/WebDashboardController.php`, `app/Http/Requests/Auth/ForgotPasswordRequest.php`
  - Added authentication throttles, made dashboard logout POST-only, used enumeration-resistant password recovery responses, improved transaction handling, and stopped logging raw exception details or submitted email values.
- **Product and purchase:** `app/Http/Requests/StoreProductRequest.php`, `app/Http/Requests/UpdateProductRequest.php`, `app/Http/Resources/ProductResource.php`, `app/Http/Resources/PurchaseResource.php`, `app/Models/Brand.php`, `app/Models/Category.php` (renamed from `category.php`), `app/Models/Product.php`, `app/Models/Purchase.php`, `app/Models/PurchaseDetail.php`, `app/Models/Supplier.php`, `app/Models/Tax.php`, `app/Models/Unit.php`, `app/Models/Warehouse.php`, `app/Repositories/ProductRepository.php`, `app/Services/ProductService.php`, `app/Services/SupplierService.php`, `app/Services/WarehouseService.php`
  - Corrected Linux PSR-4 and namespace casing, added missing product-side relationships and eager loading, validated optional relationship IDs, fixed supplier serialization, and made product write/file cleanup transaction-aware.
- **Authentication:** `app/Repositories/AuthRepository.php`
  - Wrapped related writes in transactions, avoided disclosing unknown reset-request addresses, and revoked existing API tokens after successful password reset.
- **Documentation:** `README.md`, `docs/FOUNDATION_AUDIT.md`
  - Replaced setup guidance with local/Docker instructions, documented API auth and operational caveats, and retained the audit as the pre-change findings record.
- **This report:** `docs/FOUNDATION_IMPLEMENTATION.md` records changes, actual validation, and outstanding work.

## Verified fixes

- Removed fixed app/database secrets from `.env.example`; Docker no longer replaces `APP_KEY` or runs database migrations on startup.
- Corrected the PHP extension build list and Composer source, fixed Compose startup dependency/health handling, and kept MySQL data on its existing named volume.
- Corrected the `Category` filename and `Purchase` import casing for Linux PSR-4, fixed purchase supplier serialization, and added product relationships, eager loading, relationship validation, and additive foreign keys.
- Added transaction boundaries for product persistence and account registration/reset flows. Product image cleanup now tracks rollback and replacement cases.
- Added throttling for API and dashboard authentication flows, removed GET dashboard logout, reduced password-reset enumeration, and revoked reset users' existing API tokens.
- Removed broad exception catches from customer, supplier, and warehouse controllers so unexpected failures reach Laravel's exception handler instead of exposing exception messages or being hidden behind generic action errors. Missing bound records return framework 404 responses. Sanitized service failure logging and corrected environment-variable case handling.
- New sample environments encrypt sessions; Sanctum token expiration is configurable but intentionally has no default to avoid an unreviewed lifetime change.

## Tests and checks

| Check | Result |
|---|---|
| `docker compose run --rm --no-deps app php artisan test` | **Passed: 30 tests, 114 assertions** |
| `docker compose run --rm --no-deps -e SESSION_ENCRYPT=true -e APP_KEY=[temporary test key] app php artisan test --filter=WebAuthSecurityTest` | **Passed: 3 tests, 18 assertions** |
| `docker compose run --rm --no-deps app vendor/bin/pint --test [30 changed PHP paths]` | **Passed: 30 files** |
| `composer validate --strict --no-check-publish` | **Passed** |
| `docker compose config --quiet` | **Passed** |
| `docker compose build app` | **Passed** |
| `docker compose run --rm --no-deps app composer check-platform-reqs` | **Passed** |
| Full-repository Pint check | **Not clean: 109 pre-existing style issues across 171 files**; unrelated files were not reformatted |
| Host `php artisan test` | **Blocked:** host PHP is 8.3.33, below the dependency requirement of PHP 8.4.1+ |

The test and Pint containers were run with `docker compose run --rm --no-deps`; Compose-only required interpolation variables were supplied as temporary non-production values. The MySQL service was not started for these local checks. No persistent database volume or production data was used or changed. The changed-file Pint check was repeated after formatting the last controller; the full test suite passed after the configuration changes.

## Docker and CI status

- `docker-compose.yml` remains a development stack using Laravel's built-in server. MySQL and phpMyAdmin host bindings are localhost-only; the MySQL data volume is persistent.
- CI is configured to test against a workflow-owned MySQL 8 service with non-production credentials and an ephemeral app key. The workflow has **not** yet run on GitHub.
- Local tests used in-memory SQLite; the MySQL CI path has not been independently executed in this environment.
- No PHPStan or other static-analysis tool is configured. Full-repository Pint has baseline findings, so CI checks changed PHP files only.

## Known issues and unresolved blockers

1. **Migration preflight:** the product foreign-key migration may fail if existing product rows reference missing records. Inspect deployment data and resolve inconsistencies through an approved migration before applying it. The migration does not delete or rewrite records.
2. **Secrets and key lifecycle:** supply unique production secrets through a secret manager. Do not change an `APP_KEY` already in use. Existing `.env` files are not rewritten by these changes.
3. **Token policy:** Sanctum tokens remain non-expiring by default for compatibility. Set and validate `SANCTUM_EXPIRATION` (minutes) and plan any effect on already-issued tokens before production rollout.
4. **Session rollout:** session encryption is enabled in the sample only. Existing environments need a valid, stable application key and a planned rollout; do not rotate keys casually.
5. **Mail and debug settings:** `.env.example` uses `MAIL_MAILER=log` and `APP_DEBUG=true` for local development. Configure protected production mail delivery and disable debug before deployment; the log mailer writes reset links to logs.
6. **Serving/operations:** Docker Compose runs the development server and includes phpMyAdmin; it is not a production serving, TLS, backup, or monitoring configuration.
7. **Validation gaps:** collection filter/page-size bounds and resource-level policies are not comprehensive. Route permission middleware remains the current authorization boundary; add record-level policies if ownership or tenancy rules are introduced.
8. **Error body compatibility:** uncaught customer, supplier, and warehouse errors now use Laravel's exception response instead of bespoke error envelopes or raw exception messages. Successful response contracts and routes are unchanged; clients depending on those failure formats should verify their handling.
9. **Quality baseline:** unrelated full-repository Pint findings remain. PHPStan is not configured, and remote CI has not yet been observed passing.
10. **Host toolchain:** use PHP 8.4.1+ for host commands or the provided PHP 8.4 container.

## Recommended next development phase

Before production deployment, review and apply the product migration against a non-production copy of each target schema, configure secret management, production mail, debug/session/token policies, and a production-grade serving/operations stack, then observe the GitHub MySQL CI workflow passing. After those foundation gates, proceed to the separately planned stock-management engine; sales and other roadmap modules remain out of scope here.
