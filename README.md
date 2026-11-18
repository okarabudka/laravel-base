<!-- laravel-base template readme -->
# laravel-base

[![CI](https://github.com/okarabudka/laravel-base/actions/workflows/ci.yml/badge.svg)](https://github.com/okarabudka/laravel-base/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/okarabudka/laravel-base)](https://packagist.org/packages/okarabudka/laravel-base)

A starter kit for new Laravel projects: one command gives you a named project with a Docker environment, strict
defaults, tests and CI, and the same repository builds a production image with a queue worker and scheduler.

![Start page and make help](docs/screenshot.png)

## Create a project

With the Laravel installer:

```bash
laravel new my-app --using=okarabudka/laravel-base
cd my-app && make up
```

Or with **Use this template** on GitHub:

```bash
git clone <your-repository-url> my-app && cd my-app
make init NAME=my-app
make up
```

`make init` (or the installer hook) sets `APP_NAME`, the Composer name (`<github-owner>/my-app`) and replaces this
README with a short project README that carries your CI badge. It is safe to run again.

Open https://localhost:8443 (self-signed certificate) and Mailpit at http://localhost:8025.

## What's inside

- Laravel 12 on PHP 8.5, no domain code, SQLite by default.
- Docker Compose for development: Nginx (HTTP and HTTPS), PHP-FPM built from `php:8.5-fpm-alpine`, Mailpit,
  Vite with hot reload in a Node container; MariaDB and Redis as an optional profile.
- Strict defaults: Eloquent strict mode outside production, immutable dates, destructive database commands blocked
  in production. Each one is covered by a test.
- Pest 4 with architecture presets, Laravel Pint, Larastan (PHPStan level `max`, tests included).
- GitHub Actions: style, static analysis and tests, a secret scan with gitleaks, a Docker smoke test, a run of a
  freshly initialised project and a production image check. Dependabot keeps Composer, npm, Actions and images current.

## Make targets

| Target           | What it does                                                    |
|------------------|-----------------------------------------------------------------|
| `make init`      | Rename the template copy: `make init NAME=my-app [VENDOR=acme]` |
| `make up`        | Build and start containers, install dependencies, migrate       |
| `make dev`       | `make up` plus the Vite dev server with hot reload              |
| `make down`      | Stop and remove containers                                      |
| `make sh`        | Open a shell in the PHP container                               |
| `make test`      | Run the test suite (Pest)                                       |
| `make lint`      | Check code style (Pint) and run PHPStan                         |
| `make fix`       | Fix code style (Pint)                                           |
| `make help`      | List all targets                                                |

## Development services and ports

| Service | Image                       | Port (host)                     |
|---------|-----------------------------|---------------------------------|
| web     | `nginx:1.27-alpine`         | 8443 (HTTPS), 8080 (HTTP)       |
| php     | `docker/php/Dockerfile`     | —                               |
| mail    | `axllent/mailpit`           | 8025 (UI), SMTP inside network  |
| node    | `node:22-alpine` (`dev`)    | 5173 (Vite, HTTPS)              |
| db      | `mariadb:11.4` (`full`)     | —                               |
| redis   | `redis:7-alpine` (`full`)   | —                               |

Ports can be changed in `.env` (`HTTP_PORT`, `HTTPS_PORT`, `MAILPIT_PORT`). To use MariaDB and Redis, run
`docker compose --profile full up -d` and switch the connection in `.env` (`DB_CONNECTION=mariadb`, `DB_HOST=db`
and the commented `DB_*` values).

## Production

`docker/php/Dockerfile` has two production targets: `app` (PHP-FPM with Composer dependencies without dev packages,
optimised autoloader, OPcache and built assets) and `web` (Nginx serving the public files). `compose.prod.yaml` runs
them with a queue worker and the scheduler:

```bash
cp .env.example .env    # set APP_KEY, APP_URL and your database
docker compose -f compose.prod.yaml up -d --build --wait
curl http://localhost:8080/up
```

The `app` container runs migrations on start and caches config, routes and views. TLS is expected to terminate in
front of `web` (a load balancer or reverse proxy).

## Status

Maintained.

## License

MIT, see [LICENSE](LICENSE).
