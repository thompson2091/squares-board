<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $board_id
 * @property string $quarter
 * @property int $team_row_score
 * @property int $team_col_score
 * @property int|null $team_row_2mw_score
 * @property int|null $team_col_2mw_score
 * @property bool $is_final
 * @property string $source
 * @property string|null $external_game_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Board $board
 *
 * @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<GameScore>>
 */
class GameScore extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<GameScore>> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'game_scores';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'board_id',
        'quarter',
        'team_row_score',
        'team_col_score',
        'team_row_2mw_score',
        'team_col_2mw_score',
        'is_final',
        'source',
        'external_game_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'team_row_score' => 'integer',
            'team_col_score' => 'integer',
            'team_row_2mw_score' => 'integer',
            'team_col_2mw_score' => 'integer',
            'is_final' => 'boolean',
        ];
    }

    /**
     * Valid quarters for game scores.
     *
     * @var list<string>
     */
    public const QUARTERS = ['Q1', 'Q2', 'Q3', 'final'];

    /**
     * Human-readable labels for quarters.
     *
     * @var array<string, string>
     */
    public const QUARTER_LABELS = [
        'Q1' => '1st Quarter',
        'Q2' => 'Halftime',
        'Q3' => '3rd Quarter',
        'final' => 'Final',
    ];

    /**
     * Valid score sources.
     *
     * @var list<string>
     */
    public const SOURCES = ['manual', 'api', 'import'];

    /**
     * Get the board that this score belongs to.
     *
     * @return BelongsTo<Board, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * Get the last digit of the row team score.
     */
    public function getRowDigitAttribute(): int
    {
        return $this->team_row_score % 10;
    }

    /**
     * Get the last digit of the column team score.
     */
    public function getColDigitAttribute(): int
    {
        return $this->team_col_score % 10;
    }

    /**
     * Get the last digit of the row team 2MW score.
     */
    public function getRow2mwDigitAttribute(): ?int
    {
        return $this->team_row_2mw_score !== null ? $this->team_row_2mw_score % 10 : null;
    }

    /**
     * Get the last digit of the column team 2MW score.
     */
    public function getCol2mwDigitAttribute(): ?int
    {
        return $this->team_col_2mw_score !== null ? $this->team_col_2mw_score % 10 : null;
    }

    /**
     * Check if 2MW scores are set.
     */
    public function has2mwScores(): bool
    {
        return $this->team_row_2mw_score !== null && $this->team_col_2mw_score !== null;
    }

    /**
     * Check if this quarter supports 2MW scores.
     */
    public function supports2mw(): bool
    {
        return in_array($this->quarter, ['Q2', 'final'], true);
    }

    /**
     * Get the score display string.
     */
    public function getScoreDisplayAttribute(): string
    {
        return $this->team_row_score.'-'.$this->team_col_score;
    }

    /**
     * Get a human-readable quarter name.
     */
    public function getQuarterDisplayAttribute(): string
    {
        return self::QUARTER_LABELS[$this->quarter] ?? $this->quarter;
    }
}
