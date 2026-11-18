# Makefile — local Docker workflow. Run `make help` for the list of targets.

SHELL := /bin/bash
.DEFAULT_GOAL := help

export UID := $(shell id -u)
export GID := $(shell id -g)

COMPOSE := docker compose
# -T keeps exec usable without a TTY (CI, scripts).
EXEC := $(COMPOSE) exec -T php

.PHONY: help certs env up down sh test lint fix

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-8s\033[0m %s\n", $$1, $$2}'

certs: ## Create a self-signed TLS certificate (skipped if present)
	@sh docker/conf/ssl/generate.sh

env: ## Create .env and the SQLite database file (skipped if present)
	@test -f .env || cp .env.example .env
	@touch database/database.sqlite

up: certs env ## Build and start containers, install dependencies, migrate
	$(COMPOSE) up -d --build
	$(EXEC) composer install --no-interaction
	@grep -q '^APP_KEY=.\+' .env || $(EXEC) php artisan key:generate
	$(EXEC) php artisan migrate --force

down: ## Stop and remove containers
	$(COMPOSE) down

sh: ## Open a shell in the PHP container
	$(COMPOSE) exec php sh

test: ## Run the test suite
	$(EXEC) composer test

lint: ## Check code style (Pint) and run static analysis (PHPStan)
	$(EXEC) composer lint

fix: ## Fix code style (Pint)
	$(EXEC) composer fix
