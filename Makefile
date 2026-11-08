.DEFAULT_GOAL := help

export HOST_UID := $(shell id -u)
export HOST_GID := $(shell id -g)

COMPOSE ?= docker compose
# Préfixe des commandes PHP : le conteneur php, en local comme en CI.
EXEC ?= $(COMPOSE) exec -T php
CONSOLE = $(EXEC) php bin/console
COMPOSER = $(EXEC) composer

include Makefile.qa.mk

help: ## Affiche cette aide
	@grep -hE '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2}'

install: build up ## Construit l'image, lance les conteneurs et installe les dépendances
	$(COMPOSER) install

build: ## Construit l'image Docker de développement
	$(COMPOSE) build

up: ## Lance l'API (http://localhost:8090), PostgreSQL et Mailpit (http://localhost:8026)
	$(COMPOSE) up -d --wait

down: ## Arrête les conteneurs (la base est conservée dans son volume)
	$(COMPOSE) down

logs: ## Suit les journaux des conteneurs
	$(COMPOSE) logs -f

sh: ## Ouvre un shell dans le conteneur PHP
	$(COMPOSE) exec php sh

db-reset: ## Recrée la base de dev, joue les migrations et charge les fixtures
	$(CONSOLE) doctrine:database:drop --force --if-exists
	$(CONSOLE) doctrine:database:create
	$(CONSOLE) doctrine:migrations:migrate --no-interaction
	$(CONSOLE) doctrine:fixtures:load --no-interaction

admin: ## Crée un compte de l'admin /admin, ou change son mot de passe : make admin EMAIL=moi@exemple.fr
	@test -n "$(EMAIL)" || { echo "Préciser l'adresse : make admin EMAIL=moi@exemple.fr"; exit 1; }
	$(COMPOSE) exec php php bin/console app:admin:create "$(EMAIL)"

openapi: ## Exporte le schéma OpenAPI dans openapi.json
	$(CONSOLE) api:openapi:export --output=openapi.json

docker-build-prod: ## Construit l'image de production
	docker build --target prod -t boilerplate-api-platform:prod .

.PHONY: help install build up down logs sh db-reset admin openapi docker-build-prod
