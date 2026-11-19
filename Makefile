# Makefile — local Docker workflow. Run `make help` for the list of targets.

SHELL := /bin/bash
.DEFAULT_GOAL := help

export UID := $(shell id -u)
export GID := $(shell id -g)

COMPOSE := docker compose
# -T keeps exec usable without a TTY (CI, scripts).
EXEC := $(COMPOSE) exec -T php

.PHONY: help init app sync-template certs env up dev down sh test coverage mutate lint fix

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-8s\033[0m %s\n", $$1, $$2}'

init: app ## Turn this copy into your project: make init NAME=my-app [VENDOR=acme]
	@sh bin/init "$(NAME)" "$(VENDOR)"

app: ## Install the latest Laravel and apply the template (first run only)
	@test -f artisan || { $(COMPOSE) build php && $(COMPOSE) run --rm --no-deps php sh bin/generate; }
	@test -f package-lock.json || $(COMPOSE) --profile dev run --rm --no-deps node sh bin/lock-assets

sync-template: ## Show template changes since this project started; APPLY=1 takes over infrastructure
	@sh bin/sync-template

certs: ## Create a self-signed TLS certificate (skipped if present)
	@sh docker/conf/ssl/generate.sh

env: ## Create .env and the SQLite database file (skipped if present)
	@test -f .env || cp .env.example .env
	@touch database/database.sqlite

up: certs app env ## Build and start containers, install dependencies, migrate
	$(COMPOSE) up -d --build
	$(EXEC) composer install --no-interaction
	@grep -q '^APP_KEY=.\+' .env || $(EXEC) php artisan key:generate
	$(EXEC) php artisan migrate --force

dev: up ## Start the Vite dev server with hot reload (https://localhost:5173)
	$(COMPOSE) --profile dev up -d node
	@echo "Vite: https://localhost:5173  (logs: docker compose logs -f node)"

down: ## Stop and remove containers
	$(COMPOSE) --profile dev down

sh: ## Open a shell in the PHP container
	$(COMPOSE) exec php sh

test: ## Run the test suite
	$(EXEC) composer test

coverage: ## Run the tests with code coverage (fails below 80 %)
	$(EXEC) composer coverage

mutate: ## Run mutation testing on covered code (fails below 70 %)
	$(EXEC) composer mutate

lint: ## Check code style (Pint) and run static analysis (PHPStan)
	$(EXEC) composer lint

fix: ## Fix code style (Pint)
	$(EXEC) composer fix
