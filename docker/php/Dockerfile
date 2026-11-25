# Targets: dev (local, code mounted), app (production PHP-FPM), web (production Nginx with built assets).

FROM php:8.5-fpm-alpine AS base

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions pdo_sqlite pdo_mysql intl zip bcmath opcache redis pcntl

WORKDIR /var/www


FROM base AS dev

# Code coverage for `make coverage` and `make mutate`.
RUN install-php-extensions pcov

# Match the host user so files written to the mounted project stay editable.
ARG UID=1000
ARG GID=1000
RUN addgroup -g "${GID}" app && adduser -D -u "${UID}" -G app app

USER app


FROM node:22-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build


FROM base AS vendor

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist


FROM base AS app

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    LOG_STDERR_FORMATTER=Monolog\\Formatter\\JsonFormatter

COPY docker/php/conf.d/production.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY --from=vendor /var/www/vendor ./vendor
COPY --chown=www-data:www-data . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && php artisan package:discover --ansi \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs database/sqlite \
    && chown -R www-data:www-data storage bootstrap/cache database

USER www-data

COPY --chmod=755 docker/php/entrypoint.sh /usr/local/bin/entrypoint
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

HEALTHCHECK --interval=10s --timeout=3s --retries=5 CMD pgrep -f "php-fpm: master" > /dev/null || exit 1


FROM nginx:1.31-alpine AS web

COPY docker/conf/nginx/production.conf /etc/nginx/conf.d/default.conf
COPY public /var/www/public
COPY --from=assets /app/public/build /var/www/public/build

HEALTHCHECK --interval=10s --timeout=3s --retries=5 CMD wget -qO /dev/null http://127.0.0.1/up || exit 1


# Single-container image (Nginx + PHP-FPM) for platforms that route HTTP to one container, such as Kamal.
FROM app AS server

USER root

RUN apk add --no-cache nginx \
    && rm -f /etc/nginx/http.d/default.conf

COPY docker/conf/nginx/production.conf /etc/nginx/http.d/app.conf
RUN sed -i 's#fastcgi_pass app:9000;#fastcgi_pass 127.0.0.1:9000;#' /etc/nginx/http.d/app.conf \
    && grep -q 'fastcgi_pass 127.0.0.1:9000;' /etc/nginx/http.d/app.conf

COPY --chmod=755 docker/php/server.sh /usr/local/bin/server
CMD ["server"]

HEALTHCHECK --interval=10s --timeout=3s --retries=5 CMD wget -qO /dev/null http://127.0.0.1/up || exit 1
