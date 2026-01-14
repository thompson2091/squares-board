# Super Bowl Squares

A web application for managing Super Bowl Squares games. Create boards, invite players, track payments, and automatically calculate winners based on game scores.

## Features

- **Board Management**: Create and manage multiple squares boards
- **Square Claiming**: Players can claim squares with configurable limits per user
- **Payment Tracking**: Track who has paid for their squares
- **Flexible Payouts**: Configure payout rules by quarter with multiple winner types:
  - Primary (exact score match)
  - Reverse (swapped digits)
  - Touching (adjacent squares)
  - 2-Minute Warning (Q2/Final halftime scores)
- **Auto-calculated Winners**: Winners automatically calculated when scores are entered
- **Public/Private Boards**: Share boards publicly or keep them invite-only

## Requirements

- Docker
- Docker Compose
- Node.js (for git hooks)

## Local Development Setup

### 1. Clone the repository

```bash
git clone <repository-url>
cd squares-board
```

### 2. Configure environment

```bash
cp .env.example .env
```

Edit `.env` and configure your database connection.

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

### 7. Build frontend assets

```bash
docker compose run --rm npm run build
```

### 8. Access the application

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

# Build frontend assets
docker compose run --rm npm run build

# Run dev server with hot reload
docker compose run --rm npm run dev
```

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

### Manual commands

```bash
# Run code style fixer
docker compose run --rm composer exec pint

# Run static analysis
docker compose run --rm composer exec phpstan analyse

# Run tests
docker compose run --rm artisan test
```

## Tech Stack

- **Backend**: Laravel 12, PHP 8.5
- **Frontend**: Blade, Tailwind CSS, Alpine.js
- **Database**: MySQL
- **Containerization**: Docker
