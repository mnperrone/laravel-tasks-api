.PHONY: help build up down restart logs shell composer artisan migrate seed test install clean

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

build: ## Build Docker containers
	docker-compose build

up: ## Start Docker containers
	docker-compose up -d

down: ## Stop Docker containers
	docker-compose down

restart: ## Restart Docker containers
	docker-compose restart

logs: ## Show Docker logs
	docker-compose logs -f

shell: ## Access PHP container shell
	docker-compose exec php bash

composer: ## Run composer install
	docker-compose exec php composer install

artisan: ## Run artisan command (usage: make artisan CMD="migrate")
	docker-compose exec php php artisan $(CMD)

migrate: ## Run database migrations
	docker-compose exec php php artisan migrate --force

seed: ## Run database seeders
	docker-compose exec php php artisan db:seed --force

migrate-fresh: ## Fresh migration with seed
	docker-compose exec php php artisan migrate:fresh --seed

test: ## Run tests
	docker-compose exec php php artisan test

install: build up composer ## Install and setup project
	@echo "Waiting for Postgres to be ready..."
	@docker-compose exec -T postgres bash -lc 'until pg_isready -U "$$POSTGRES_USER" -d "$$POSTGRES_DB" >/dev/null 2>&1; do printf "."; sleep 1; done'
	docker-compose exec php php artisan key:generate
	docker-compose exec php php artisan jwt:secret
	docker-compose exec php php artisan migrate --force
	docker-compose exec php php artisan db:seed --force
	@echo "Installation complete! Access the app at http://localhost:8080"

clean: ## Clean up containers and volumes
	docker-compose down -v

key-generate: ## Generate application key
	docker-compose exec php php artisan key:generate

jwt-secret: ## Generate JWT secret
	docker-compose exec php php artisan jwt:secret

cache-clear: ## Clear application cache
	docker-compose exec php php artisan cache:clear
	docker-compose exec php php artisan config:clear
	docker-compose exec php php artisan route:clear
	docker-compose exec php php artisan view:clear

optimize: ## Optimize application
	docker-compose exec php php artisan config:cache
	docker-compose exec php php artisan route:cache
	docker-compose exec php php artisan view:cache
