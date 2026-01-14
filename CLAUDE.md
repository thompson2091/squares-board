# Claude Instructions

This is a Super Bowl Squares web application built with Laravel 12 and PHP 8.5.

## Project Overview

Super Bowl Squares is a popular betting game where a 10x10 grid represents all possible score combinations. Players claim squares, and winners are determined by the last digit of each team's score at the end of each quarter.

## Tech Stack

- **Backend**: Laravel 12, PHP 8.5
- **Frontend**: Blade templates, Tailwind CSS, Alpine.js
- **Database**: MySQL
- **Containerization**: Docker

## Key Concepts

### Board
A game board with 100 squares (10x10 grid). Each board has:
- Two teams (row team and column team)
- Price per square
- Max squares per user limit
- Row/column numbers (0-9, randomly assigned when board is locked)
- Status: `draft`, `open`, `locked`, `completed`

### Square
A single cell in the 10x10 grid. Can be claimed by a user and marked as paid.

### Payout Rules
Configurable rules determining how the pot is distributed. Each rule specifies:
- Quarter: Q1, Q2, Q3, or final
- Winner type: `primary`, `reverse`, `touching`, `2mw`
- Payout type: percentage or fixed amount
- Amount stored in basis points (percentage) or cents (fixed)

### Winner Types
- **Primary**: Exact match - row digit matches row team score, col digit matches col team score
- **Reverse**: Swapped digits - row digit matches col team score and vice versa
- **Touching**: Four squares adjacent to the primary winner (with wrap-around)
- **2MW (2-Minute Warning)**: Based on score at 2-minute warning of Q2/Final (halftime scores)

### Winners
Calculated automatically when game scores are entered. Each winner record links to:
- The winning square
- The user who owns it
- The quarter and payout amount
- Boolean flags: `is_reverse`, `is_touching`, `is_2mw`

## Money Storage

All monetary values are stored as integers:
- **Prices/payouts**: Stored in cents (e.g., $10.00 = 1000)
- **Percentages**: Stored in basis points (e.g., 25% = 2500)

Always divide by 100 when displaying to users.

## Key Files

- `app/Models/Board.php` - Board model with status constants
- `app/Models/Square.php` - Square model with claim/release methods
- `app/Models/PayoutRule.php` - Payout configuration with quarter/type constants
- `app/Models/Winner.php` - Calculated winner records
- `app/Models/GameScore.php` - Quarter scores with digit accessors
- `app/Services/WinnerCalculatorService.php` - Winner calculation logic
- `app/Http/Controllers/ScoreController.php` - Score entry and winner calculation
- `app/Http/Controllers/PayoutController.php` - Payout rule management

## Common Commands

```bash
# Run code style fixer
docker compose run --rm composer exec pint

# Run static analysis (level 8)
docker compose run --rm composer exec phpstan analyse

# Run tests
docker compose run --rm artisan test

# Run migrations
docker compose run --rm artisan migrate
```

## Code Standards

- PHPStan level 8 (strict typing)
- Laravel Pint for code style
- All new code must pass static analysis
- Use strict types in all PHP files
