.PHONY: up down build install migrate seed fresh logs shell mysql

# Start containers
up:
	docker-compose up -d

# Stop containers
down:
	docker-compose down

# Build containers
build:
	docker-compose build

# Install dependencies and setup
install:
	cp .env.example .env
	@echo "Edit .env and set DB_PASSWORD before continuing"
	docker-compose up -d
	docker-compose exec app composer install
	docker-compose exec app php artisan key:generate
	docker-compose exec app php artisan migrate
	docker-compose exec app php artisan db:seed
	@echo "Done! Open http://localhost:8080"

# Run migrations
migrate:
	docker-compose exec app php artisan migrate

# Run seeders
seed:
	docker-compose exec app php artisan db:seed

# Fresh migration with seed
fresh:
	docker-compose exec app php artisan migrate:fresh --seed

# View logs
logs:
	docker-compose logs -f

# Shell into app container
shell:
	docker-compose exec app bash

# Shell into mysql container (will prompt for password)
mysql:
	docker-compose exec mysql mysql -u root -p domain_management

# Run queue worker manually
queue:
	docker-compose exec app php artisan queue:work

# Clear all caches
clear:
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

# Restart containers
restart:
	docker-compose restart
