# Vaskekalender – Laundry-Room Booking System

A reliable, browser-based laundry-room booking system for a residential
association. Residents share one property access code (no individual
accounts). The public interface is in Danish.

Built conservatively with plain PHP 8.3+, PDO, server-rendered HTML,
and minimal vanilla JavaScript. No PHP framework, no JavaScript
framework, no WebSockets.

## Features

- Shared property-code access for residents (no accounts).
- Weekly calendar with four fixed daily slots: `07:00–10:00`,
  `10:00–13:00`, `13:00–16:00`, `16:00–19:00`.
- Booking with a required, visible name and a mandatory privacy
  checkbox.
- A six-digit cancellation code per booking, shown once, stored only
  as a `password_hash()`.
- Manual 30-minute takeover rule (displayed, never enforced
  automatically).
- Separate admin authentication with its own session, rate limiting,
  dashboard, booking management, CSV export, settings, and activity
  log.
- CSRF protection, prepared statements, output escaping, rate
  limiting, and secure session cookies.
- Core booking and cancellation flows work without JavaScript.

## Requirements

- PHP 8.3+
- MySQL or MariaDB
- Git
- Composer (for autoloading and the dev-only PHPUnit dependency)

## Local development

```bash
git clone git@github.com:YOUR-USERNAME/laundry-booking.git
cd laundry-booking
cp .env.example .env
composer install
```

Create the database:

```sql
CREATE DATABASE laundry_booking
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Generate the required hashes and put them in `.env`:

```bash
php -r "echo password_hash('change-this-property-code', PASSWORD_DEFAULT) . PHP_EOL;"
php -r "echo password_hash('change-this-admin-password', PASSWORD_DEFAULT) . PHP_EOL;"
```

Set `RESIDENT_PROPERTY_CODE_HASH` and `ADMIN_PASSWORD_HASH` in `.env`
to the generated hashes, and `ADMIN_USERNAME` to the desired admin
username.

Run the migrations and start the built-in server:

```bash
php scripts/run_migrations.php
php -S localhost:8000 -t public
```

Open <http://localhost:8000>.

## Testing

```bash
composer install
composer test
```

`tests/CodeServiceTest.php` runs without a database. Tests in
`tests/BookingServiceTest.php` require a working database connection
configured through `.env` (they are skipped automatically if
`DB_DATABASE` is not set) and truncate the `bookings` and
`activity_logs` tables before each test, so point `.env` at a
disposable development/test database, not production.

## Project structure

See `app/`, `public/`, `scripts/`, and `tests/` for the application
code, web-accessible entry points, operational scripts, and automated
tests respectively. Only `public/` is meant to be exposed by the web
server; the document root must be set to `public/`.

## Admin panel

Visit `/admin/login.php` with the username and password configured via
`ADMIN_USERNAME` / `ADMIN_PASSWORD_HASH`. From there an administrator
can:

- View dashboard counts and system status (`/admin/index.php`).
- Filter, search, and delete bookings (`/admin/bookings.php`).
- Export all bookings as CSV (`/admin/export.php`).
- Change the booking window, calendar message, and the shared
  property code (`/admin/settings.php`).
- Review the activity log (`/admin/logs.php`).

Do not use the shared kiosk tablet for admin access.

## Operations

- `scripts/run_migrations.php` — applies pending SQL migrations from
  `app/Database/migrations/`; safe to re-run.
- `scripts/cleanup_old_bookings.php` — deletes bookings and activity
  logs older than `OLD_BOOKING_RETENTION_DAYS` (default 180 days).
  Schedule this with a cron job or the host's scheduled-tasks feature.
- `scripts/backup_db.php` — dumps the database to a timestamped file
  in `storage/backups/` using `mysqldump` and prunes backups older
  than `BACKUP_RETENTION_DAYS` (default 30 days). If `mysqldump` is
  not available on the hosting environment, use the control panel's
  own database export tool (e.g. phpMyAdmin's "Export" feature on
  Simply.com) instead, and manage retention manually.

## Deployment

Deployment is manual only, triggered from the GitHub Actions "Deploy"
workflow (`workflow_dispatch`). It checks out the repository, installs
PHP 8.3 and production Composer dependencies, builds an artifact that
excludes `.env`, Git metadata, `storage/backups`, `storage/logs`, and
`tests/`, and uploads it over SFTP using credentials stored in GitHub
Secrets (`DEPLOY_HOST`, `DEPLOY_USERNAME`, `DEPLOY_PASSWORD`,
`DEPLOY_PORT`, `DEPLOY_REMOTE_PATH`). Never commit deployment
credentials to the repository.

### Production checklist

1. Create the domain or subdomain and enable HTTPS.
2. Create the production database and a dedicated database user.
3. Set the web server's document root to `public/`.
4. Upload the application (via the deploy workflow or manually).
5. Create a production `.env` (never commit it) with `APP_ENV=production`
   and `APP_DEBUG=false`.
6. Run `php scripts/run_migrations.php` on the server.
7. Generate the property-code and admin-password hashes and set them
   in the production `.env`.
8. Test `/health.php`.
9. Test booking creation and cancellation end to end.
10. Test admin login and booking deletion.
11. Configure scheduled backups and cleanup.
12. Store admin credentials securely outside Git.

## Security notes

- HTTPS is required in production; session cookies are marked
  `Secure` automatically when the request is served over HTTPS.
- Every POST request is protected by a CSRF token.
- All user-supplied output is escaped with `e()` before being printed.
- Cancellation codes are only ever stored as `password_hash()` values
  and are never logged or shown again after the initial booking
  confirmation.
- Resident and admin access, and cancellation attempts, are rate
  limited (5 failed attempts locks out for 5 minutes).
- Production error handling shows a generic Danish message instead of
  stack traces.

## Out of scope for version 1

Multiple rooms/machines, resident accounts, email/SMS reminders,
maintenance blocks, public-holiday rules, QR codes, language
switching, PWA installation, and advanced audit-log search are
intentionally not implemented.
