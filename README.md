<!-- laravel-base template readme -->
# laravel-base

[![CI](https://github.com/okarabudka/laravel-base/actions/workflows/ci.yml/badge.svg)](https://github.com/okarabudka/laravel-base/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/okarabudka/laravel-base)](https://packagist.org/packages/okarabudka/laravel-base)

A starter kit for new Laravel projects. It contains no business logic. It covers the setup every new project needs
before the first feature: a local environment, quality checks, CI and a way to ship to production. You get all of
it with one command and can start on the actual product.

![Start page after make up](docs/screenshot.png)

## Why it exists

The first day of a new Laravel project usually looks the same: write a `docker-compose.yml`, fight file permissions
in the container, set up tests and a linter, wire up CI, decide on sensible framework defaults, and only weeks later
work out how the thing will actually run in production. Each project solves this slightly differently, and the
differences cost time when you move between them.

laravel-base makes those decisions once and keeps them current. Every project started from it has the same
commands, the same checks and the same production setup, so `make up`, `make test` and `make lint` work in all of
them.

## What you get

**A working project in minutes.** Laravel 12 on PHP 8.5 with SQLite. `make up` creates the TLS certificate and
`.env`, builds the containers, installs dependencies, generates the app key and runs migrations. You only need
Docker, make and openssl on your machine. PHP and Node are not required.

**A local environment close to production.** Nginx with HTTPS, PHP-FPM, Mailpit to catch outgoing mail, and Vite with
hot reload in its own container. MariaDB and Redis are one profile away. Containers run as your user, so files created
inside them stay editable on the host.

**Quality checks from the first commit.**
- Pest tests, including architecture presets that keep the code structure in shape.
- Laravel Pint for code style.
- Larastan (PHPStan) at the strictest level, tests included.

The same checks run in GitHub Actions together with a secret scan (gitleaks), a Docker smoke test, a run of a freshly
initialised project and a production build.

**Strict defaults.** Eloquent fails loudly on lazy loading, unknown attributes and silently dropped fields outside
production. Dates are immutable. `migrate:fresh`, `db:wipe` and similar commands are blocked in production. Tests
cover each of these, so removing one by accident turns CI red.

**A path to production.** The same Dockerfile builds a production PHP-FPM image without dev dependencies, with an
optimised autoloader, OPcache and pre-built assets, plus an Nginx image for the public files. `compose.prod.yaml` runs
them together with a queue worker and the scheduler, with health checks on every service.

**It stays current.** Dependabot proposes updates for Composer, npm, GitHub Actions and Docker images once a month,
and CI shows whether they still pass.

## How to use it

### Start a new project

With the Laravel installer:

```bash
laravel new my-app --using=okarabudka/laravel-base
cd my-app && make up
```

Or click **Use this template** on GitHub, then:

```bash
git clone <your-repository-url> my-app && cd my-app
make init NAME=my-app
make up
```

`make init` (the installer runs it for you) renames the copy to your project:
- sets `APP_NAME`;
- sets the Composer name (`<github-owner>/my-app`);
- replaces this README with a short project README that carries your CI badge.

It is safe to run again.

### Just try it

```bash
git clone https://github.com/okarabudka/laravel-base.git && cd laravel-base
make up
```

Open https://localhost:8443 (self-signed certificate, accept the browser warning) and Mailpit at http://localhost:8025.
If a port is taken, change `HTTP_PORT`, `HTTPS_PORT` or `MAILPIT_PORT` in `.env` and run `make up` again.

### Day-to-day work

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

`make up` is idempotent: it keeps the existing certificate, `.env` and app key.

## Development services and ports

| Service | Image                       | Port (host)                     |
|---------|-----------------------------|---------------------------------|
| web     | `nginx:1.27-alpine`         | 8443 (HTTPS), 8080 (HTTP)       |
| php     | `docker/php/Dockerfile`     | —                               |
| mail    | `axllent/mailpit`           | 8025 (UI), SMTP inside network  |
| node    | `node:22-alpine` (`dev`)    | 5173 (Vite, HTTPS)              |
| db      | `mariadb:11.4` (`full`)     | —                               |
| redis   | `redis:7-alpine` (`full`)   | —                               |

To use MariaDB and Redis, run `docker compose --profile full up -d` and switch the connection in `.env`
(`DB_CONNECTION=mariadb`, `DB_HOST=db` and the commented `DB_*` values).

## Production

`docker/php/Dockerfile` has two production targets: `app` (PHP-FPM) and `web` (Nginx with the built assets).
`compose.prod.yaml` runs them with a queue worker and the scheduler:

```bash
cp .env.example .env    # set APP_KEY, APP_URL and your database
docker compose -f compose.prod.yaml up -d --build --wait
curl http://localhost:8080/up
```

On start, the `app` container runs migrations and caches config, routes and views. TLS is expected to terminate in
front of `web`, for example at a load balancer or reverse proxy. The same images can be deployed to any container
platform.

## Project layout

```
bin/init                   names a fresh copy of the template
docker/php/Dockerfile      dev, app (production PHP-FPM) and web (production Nginx) targets
docker/conf/               Nginx, MariaDB and TLS configuration
docker-compose.yml         local development stack
compose.prod.yaml          production-like stack with queue and scheduler
.github/                   CI workflow and Dependabot
```

## Status

Maintained.

## License

MIT, see [LICENSE](LICENSE).
