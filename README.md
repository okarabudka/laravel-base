# laravel-base

[![CI](https://github.com/okarabudka/laravel-base/actions/workflows/ci.yml/badge.svg)](https://github.com/okarabudka/laravel-base/actions/workflows/ci.yml)

A GitHub template for new Laravel projects: a clean Laravel 12 application with a Docker environment built from
public images, a Makefile and quality checks that run from the first commit.

## What's inside

- Laravel 12 on PHP 8.5, no domain code, SQLite by default.
- Docker Compose: Nginx (HTTP and HTTPS), PHP-FPM built from `php:8.5-fpm-alpine`, Mailpit for outgoing mail.
  MariaDB and Redis are available as an optional profile.
- Pest for tests, Laravel Pint for code style, Larastan (PHPStan, level `max`) for static analysis.
- GitHub Actions: style, static analysis and tests, a secret scan with gitleaks, and a Docker smoke test.
- Vite and Tailwind as shipped with the Laravel 12 skeleton.

## Quick start

Requires Docker with Compose v2, make and openssl.

```bash
git clone <your-repository-url> my-app && cd my-app
make up
```

Open https://localhost:8443 (self-signed certificate) and Mailpit at http://localhost:8025.
`make up` creates the certificate, `.env` and the SQLite file, builds the PHP image, installs Composer dependencies,
generates the application key and runs migrations. Running it again keeps the existing certificate and key.

## Make targets

| Target       | What it does                                              |
|--------------|-----------------------------------------------------------|
| `make up`    | Build and start containers, install dependencies, migrate |
| `make down`  | Stop and remove containers                                |
| `make sh`    | Open a shell in the PHP container                         |
| `make test`  | Run the test suite (Pest)                                 |
| `make lint`  | Check code style (Pint) and run PHPStan                   |
| `make fix`   | Fix code style (Pint)                                     |
| `make help`  | List all targets                                          |

`make up` replaces the former `install-backend` target.

## Services and ports

| Service | Image                       | Port (host)                     |
|---------|-----------------------------|---------------------------------|
| web     | `nginx:1.27-alpine`         | 8443 (HTTPS), 8080 (HTTP)       |
| php     | built from `docker/php`     | —                               |
| mail    | `axllent/mailpit`           | 8025 (UI), SMTP inside network  |
| db      | `mariadb:11.4` (`full`)     | —                               |
| redis   | `redis:7-alpine` (`full`)   | —                               |

Ports can be changed in `.env` (`HTTP_PORT`, `HTTPS_PORT`, `MAILPIT_PORT`).

To use MariaDB and Redis, start the `full` profile and switch the connection in `.env`
(`DB_CONNECTION=mariadb`, `DB_HOST=db` and the commented `DB_*` values):

```bash
docker compose --profile full up -d
```

## After creating your project

1. Set `APP_NAME` in `.env.example` and `name`/`description` in `composer.json`.
2. Point the CI badge at the new repository.
3. Replace this README.

## Status

Maintained.

## License

MIT, see [LICENSE](LICENSE).
