<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $board_id
 * @property int $square_id
 * @property int $user_id
 * @property string $quarter
 * @property int $row_score
 * @property int $col_score
 * @property int $payout_amount
 * @property bool $is_reverse
 * @property bool $is_touching
 * @property bool $is_2mw
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Board $board
 * @property-read Square $square
 * @property-read User $user
 *
 * @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<Winner>>
 */
class Winner extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<Winner>> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'winners';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'board_id',
        'square_id',
        'user_id',
        'quarter',
        'row_score',
        'col_score',
        'payout_amount',
        'is_reverse',
        'is_touching',
        'is_2mw',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'row_score' => 'integer',
            'col_score' => 'integer',
            'payout_amount' => 'integer',
            'is_reverse' => 'boolean',
            'is_touching' => 'boolean',
            'is_2mw' => 'boolean',
        ];
    }

    /**
     * Get the board that this winner belongs to.
     *
     * @return BelongsTo<Board, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * Get the square that this winner is associated with.
     *
     * @return BelongsTo<Square, $this>
     */
    public function square(): BelongsTo
    {
        return $this->belongsTo(Square::class);
    }

    /**
     * Get the user who won.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payout amount as a formatted currency string.
     */
    public function getPayoutDisplayAttribute(): string
    {
        return '$'.number_format($this->payout_amount / 100, 2);
    }

    /**
     * Get a description of the win type.
     */
    public function getWinTypeAttribute(): string
    {
        if ($this->is_2mw) {
            return '2-Min Warning';
        }

        if ($this->is_touching) {
            return 'Touching';
        }

        if ($this->is_reverse) {
            return 'Reverse';
        }

        return 'Primary';
    }

    /**
     * Get the score display string.
     */
    public function getScoreDisplayAttribute(): string
    {
        return $this->row_score.'-'.$this->col_score;
    }
}
