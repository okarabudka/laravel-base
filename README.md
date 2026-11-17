# Laravel-Base

A starter template for new Laravel projects: a clean Laravel 9 application with a Docker environment, a Makefile and code-quality tooling.

## Context

A personal starter template created by Oleksandr Karabudka in February 2023 to start new Laravel projects quickly with the same local environment and tooling every time. It received small configuration updates in 2024 (PHP-FPM image, Composer dependencies, environment settings).

## Features

- Laravel 9 application with no domain code, ready to be extended.
- Docker Compose environment: nginx with HTTP and HTTPS, PHP-FPM 8.3, optional MariaDB and Redis, MailHog for outgoing mail.
- SQLite as the default database, so the project runs without a database container.
- Makefile target `install-backend` that prepares `.env`, starts the containers, installs Composer dependencies, links storage and runs migrations.
- Composer scripts for parallel tests, code coverage and PSR-12 code style checks.
- Dev dependencies for debugging and quality: Clockwork, PHP_CodeSniffer, ParaTest.
- S3 filesystem driver (`league/flysystem-aws-s3-v3`) and Doctrine DBAL preinstalled.

## Tech stack

- PHP 8.2+, Laravel 9, Laravel Sanctum
- SQLite (default), MariaDB 10.5 and Redis available in Docker Compose
- PHPUnit 9, ParaTest, PHP_CodeSniffer (PSR-12), Clockwork
- Vite
- Docker Compose (nginx 1.20, PHP-FPM 8.3, MailHog), Makefile

## Architecture

- Standard Laravel directory layout; only the default `welcome` route and the Sanctum `/api/user` route exist.
- Infrastructure lives next to the code: `docker-compose.yml`, nginx config in `docker/conf/nginx`, TLS files in `docker/conf/ssl`, MySQL config in `docker/conf/mysql`.
- Container names are derived from `APP_NAME`, so several projects based on this template can run side by side.
- `phpcs.xml` defines the PSR-12 ruleset and excludes generated or framework-style files (middleware, Orchid screens and layouts, package migrations and views).

## Getting started

Requires Docker, Docker Compose and make.

```bash
sh docker/conf/ssl/generate.sh       # create the local self-signed TLS certificate
cp .env.example .env
touch database/database.sqlite       # SQLite file used by DB_DATABASE in .env
make install-backend                 # start containers, composer install, storage:link, migrate
```

Open http://localhost. Run `make help` to list the Makefile targets. To use MariaDB instead of SQLite, start the `db` service (`docker compose up -d db`) and set the `DB_*` values in `.env`.

## Testing

Run inside the `php` container (`docker compose exec php bash`):

```bash
composer test            # php artisan test, 3 parallel processes, stop on first failure
composer coverage-html   # HTML coverage report in storage/app/coverage
composer cs              # PHP_CodeSniffer (PSR-12)
composer cs-fix          # fix code style automatically
```

Only the default Laravel example tests are included.

## Status

Personal starter template, created in 2023 and last updated in 2024; not actively maintained.

## License

MIT, see [LICENSE](LICENSE).
