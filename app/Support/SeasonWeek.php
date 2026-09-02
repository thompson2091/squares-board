<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Board;
use Illuminate\Support\Carbon;

/**
 * One week of a season pool: a thin read view over that week's Board.
 *
 * Weekly boards are created before their matchups are known, so an unset
 * matchup is stored as the TBD sentinel rather than null - boards.team_row and
 * team_col are NOT NULL, and "TBD" is what every existing board view should
 * print for a week that hasn't been filled in yet.
 */
final class SeasonWeek
{
    public const STATUS_UPCOMING = 'upcoming';

    public const STATUS_CURRENT = 'current';

    public const STATUS_COMPLETED = 'completed';

    /** Placeholder stored in team_row/team_col until the matchup is set. */
    public const TBD = 'TBD';

    /**
     * Whether the matchup for this week has been set yet.
     */
    public readonly bool $has_matchup;

    /**
     * Human-readable matchup, e.g. "Chiefs vs Bills" or "TBD".
     */
    public readonly string $matchup;

    public function __construct(
        public readonly int $number,
        public readonly ?string $team_row = null,
        public readonly ?string $team_col = null,
        public readonly ?Carbon $game_date = null,
        public readonly string $status = self::STATUS_UPCOMING,
        public readonly bool $numbers_revealed = false,
        public readonly ?Board $board = null,
    ) {
        $this->has_matchup = $team_row !== null && $team_col !== null;
        $this->matchup = $this->has_matchup
            ? $team_row.' vs '.$team_col
            : self::TBD;
    }

    /**
     * Build a week from its board.
     *
     * @param  int|null  $currentWeek  the season's current week number
     */
    public static function fromBoard(Board $board, ?int $currentWeek = null): self
    {
        $number = (int) $board->week_number;

        $status = match (true) {
            $board->isCompleted() => self::STATUS_COMPLETED,
            $number === $currentWeek => self::STATUS_CURRENT,
            default => self::STATUS_UPCOMING,
        };

        return new self(
            number: $number,
            team_row: self::team($board->team_row),
            team_col: self::team($board->team_col),
            game_date: $board->game_date,
            status: $status,
            numbers_revealed: $board->numbers_revealed,
            board: $board,
        );
    }

    public function isCurrent(): bool
    {
        return $this->status === self::STATUS_CURRENT;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Treat the TBD sentinel and blanks as "not set yet".
     */
    private static function team(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || strcasecmp($value, self::TBD) === 0) {
            return null;
        }

        return $value;
    }
}
