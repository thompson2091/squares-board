<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Board;
use App\Models\Season;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A custom URL has to be free across boards AND seasons, and must not shadow
 * one of the app's own paths.
 *
 * Both live on the same catch-all route (`/{slug}`), so checking only one table
 * would let a board quietly take over a season's link.
 */
class AvailableSlug implements ValidationRule
{
    /**
     * First path segments the app already owns.
     *
     * @var list<string>
     */
    public const RESERVED = [
        'admin',
        'boards',
        'confirm-password',
        'dashboard',
        'email',
        'forgot-password',
        'health',
        'login',
        'logout',
        'manage',
        'password',
        'privacy',
        'profile',
        'register',
        'reset-password',
        'seasons',
        'storage',
        'terms',
        'verify-email',
        'winnings',
    ];

    /**
     * @param  int|null  $ignoreBoardId  the board being edited, if any
     * @param  int|null  $ignoreSeasonId  the season being edited, if any
     */
    public function __construct(
        private readonly ?int $ignoreBoardId = null,
        private readonly ?int $ignoreSeasonId = null,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, string|null=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (in_array(strtolower($value), self::RESERVED, true)) {
            $fail('The :attribute is reserved. Please choose another.');

            return;
        }

        $boardTaken = Board::where('slug', $value)
            ->when($this->ignoreBoardId !== null, fn ($query) => $query->whereKeyNot($this->ignoreBoardId))
            ->exists();

        $seasonTaken = Season::where('slug', $value)
            ->when($this->ignoreSeasonId !== null, fn ($query) => $query->whereKeyNot($this->ignoreSeasonId))
            ->exists();

        if ($boardTaken || $seasonTaken) {
            $fail('The :attribute has already been taken.');
        }
    }
}
