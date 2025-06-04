# Laravel Docker Development Environment

A complete Docker setup for Laravel development with PHP 8.2, MySQL 8.0, and Nginx.

## Features

- **PHP 8.2** with FPM
- **Laravel** (latest version)
- **MySQL 8.0** database
- **Nginx** web server
- **Composer** for dependency management
- Hot reload for development

## Prerequisites

- Docker & Docker Compose

## Quick Start

1. **Start the environment:**
   ```bash
   docker compose up -d
   ```

2. **Access your application:**
   - Application: http://localhost:8000
   - MySQL: localhost:3306 (user: laravel, password: laravel, database: laravel)

## Available Commands

**Basic Operations:**
```bash
# Start all services
docker compose up -d

# Stop all services
docker compose down

# View logs
docker compose logs -f

# View status
docker compose ps
```

**Laravel/Artisan Commands:**
```bash
# Run any artisan command
docker compose run --rm artisan [command]

# Examples:
docker compose run --rm artisan migrate
docker compose run --rm artisan make:controller HomeController
docker compose run --rm artisan tinker
docker compose run --rm artisan test
```

**Composer Commands:**
```bash
# Install packages
docker compose run --rm composer install

# Add new packages
docker compose run --rm composer require package/name

# Update packages
docker compose run --rm composer update
```

**Container Access:**
```bash
# Access application shell
docker compose exec app bash

# Access MySQL
docker compose exec mysql mysql -u laravel -p laravel
```

## Services

### Application (app)
- **Container**: laravel_app
- **PHP**: 8.2-fpm
- **Extensions**: PDO MySQL, mbstring, exif, pcntl, bcmath, gd, zip

### Web Server (nginx)
- **Container**: laravel_nginx
- **Port**: 8000

### Database (mysql)
- **Container**: laravel_mysql
- **Port**: 3306
- **Database**: laravel
- **Username**: laravel
- **Password**: laravel

### Composer & Artisan
- **Container**: laravel_composer (for composer commands)
- **Container**: laravel_artisan (for artisan commands)

## Development Workflow

1. **Start the environment:**
   ```bash
   docker compose up -d
   ```

2. **Install/update dependencies:**
   ```bash
   docker compose run --rm composer install
   ```

3. **Run migrations:**
   ```bash
   docker compose run --rm artisan migrate
   ```

4. **Access application shell for development:**
   ```bash
   docker compose exec app bash
   ```

Your Laravel development environment is ready to use! The application will auto-reload when you make changes to your code.
