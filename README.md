# PantryTrak

Laravel application with Docker support for local development and AWS ECS deployment.

## Requirements

- Docker
- Docker Compose
- Node.js (for git hooks)

## Local Development Setup

### 1. Clone the repository

```bash
git clone <repository-url>
cd pantrytrak-laravel
```

### 2. Configure environment

```bash
cp .env.example .env
```

Edit `.env` and configure:
- Database connection (external MySQL host)
- Snowflake credentials (if using Snowflake integration)

### 3. Build and start containers

```bash
docker compose up -d --build
```

### 4. Install dependencies

```bash
docker compose run --rm composer install
docker compose run --rm npm install
```

### 5. Generate application key

```bash
docker compose run --rm artisan key:generate
```

### 6. Run migrations

```bash
docker compose run --rm artisan migrate
```

### 7. Access the application

Open [http://localhost:8001](http://localhost:8001) in your browser.

## Docker Commands

| Command | Description |
|---------|-------------|
| `docker compose up -d --build` | Build and start containers |
| `docker compose down` | Stop containers |
| `docker compose logs -f php` | View PHP/nginx logs |
| `docker compose run --rm composer <cmd>` | Run composer commands |
| `docker compose run --rm artisan <cmd>` | Run artisan commands |
| `docker compose run --rm npm <cmd>` | Run npm commands |

### Examples

```bash
# Install a package
docker compose run --rm composer require package/name

# Run migrations
docker compose run --rm artisan migrate

# Create a controller
docker compose run --rm artisan make:controller MyController

# Install npm dependencies
docker compose run --rm npm install

# Build frontend assets
docker compose run --rm npm run build
```

## Git Workflow

```
feature-branch → beta → main
```

1. Create feature branches from `beta`
2. Open PRs into `beta` - CI runs Pint, PHPStan, PHPUnit
3. When merged to `beta`, auto-PR is created to `main` (draft)
4. Review and merge to `main` for production

## Pre-commit Hooks

This project uses Husky + lint-staged for automated code quality checks.

### What runs on commit:

1. **Laravel Pint** - Code style formatting
2. **PHPStan Level 8** - Static analysis (strict typing)

Tests run in CI only (not on commit) for faster local development.

## CI/CD (GitHub Actions)

### CI Workflow (`ci.yml`)
Runs on PRs to `main` and `beta`:
- **Lint job**: Pint (code style) + PHPStan (static analysis)
- **Test job**: PHPUnit (requires lint to pass)

### Auto-PR Workflow (`auto-generate-main-pr.yml`)
When a PR is merged into `beta`, automatically creates a draft PR from `beta` → `main`.

### Manual commands

```bash
# Run code style fixer
docker compose run --rm composer exec pint

# Run static analysis
docker compose run --rm composer exec phpstan analyse

# Run tests
docker compose run --rm artisan test
```

## Project Structure

```
├── .github/
│   └── workflows/
│       ├── ci.yml                 # CI: Pint, PHPStan, PHPUnit
│       └── auto-generate-main-pr.yml  # Auto-PR beta → main
├── .husky/
│   └── pre-commit                 # Pre-commit hook (Pint + PHPStan)
├── app/
│   └── Services/
│       └── Snowflake.php      # Snowflake SQL API client
├── config/
│   └── snowflake.php          # Snowflake configuration
├── docker/
│   ├── dockerfile.php         # Multi-stage Dockerfile
│   ├── entrypoint.sh          # Local entrypoint
│   ├── entrypoint.prod.sh     # Production entrypoint
│   ├── nginx/
│   │   └── nginx.conf
│   └── php/
│       ├── php.ini
│       └── php-fpm.conf
├── docker-compose.yml
├── phpstan.neon               # PHPStan config (level 8)
└── .env.example
```

## Dockerfile Targets

| Target | Use Case |
|--------|----------|
| `php_local` | Local development with Xdebug |
| `php_beta` | Staging/beta deployments |
| `php_prod` | Production deployments (AWS ECS) |

## Production Build (AWS ECS)

```bash
docker build -f docker/dockerfile.php --target php_prod -t pantrytrak:latest .
```

## Snowflake Integration

The `App\Services\Snowflake` class provides JWT-authenticated access to Snowflake's SQL API.

Required `.env` variables:
```
SNOWFLAKE_ACCOUNT=
SNOWFLAKE_REGION=
SNOWFLAKE_DOMAIN=snowflakecomputing.com
SNOWFLAKE_USER=
SNOWFLAKE_DATABASE=
SNOWFLAKE_SCHEMA=
SNOWFLAKE_WAREHOUSE=
SNOWFLAKE_ROLE=
SNOWFLAKE_API_PATH=/api/v2/statements
SNOWFLAKE_PRIVATE_KEY=      # base64 encoded
SNOWFLAKE_PUBLIC_KEY=       # base64 encoded
SNOWFLAKE_PUBLIC_FINGERPRINT=
```

Usage:
```php
use App\Services\Snowflake;

$results = Snowflake::execute('SELECT * FROM my_table WHERE id = ?', [
    '1' => ['type' => 'TEXT', 'value' => '123']
]);
```
