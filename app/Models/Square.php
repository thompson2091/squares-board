<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $board_id
 * @property int|null $user_id
 * @property int $row
 * @property int $col
 * @property bool $is_paid
 * @property \Illuminate\Support\Carbon|null $claimed_at
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Board $board
 * @property-read User|null $user
 */
class Square extends Model
{
    /** @use HasFactory<\Database\Factories\SquareFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'squares';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'board_id',
        'user_id',
        'row',
        'col',
        'is_paid',
        'claimed_at',
        'paid_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'row' => 'integer',
            'col' => 'integer',
            'is_paid' => 'boolean',
            'claimed_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Get the board that this square belongs to.
     *
     * @return BelongsTo<Board, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * Get the user who claimed this square.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this square is claimed by any user.
     */
    public function isClaimed(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Check if this square is claimed by a specific user.
     */
    public function isClaimedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Claim this square for a user.
     */
    public function claim(User $user): void
    {
        $this->user_id = $user->id;
        $this->claimed_at = Carbon::now();
        $this->save();
    }

    /**
     * Release this square (unclaim it).
     */
    public function release(): void
    {
        $this->user_id = null;
        $this->claimed_at = null;
        $this->is_paid = false;
        $this->paid_at = null;
        $this->save();
    }

    /**
     * Mark this square as paid.
     */
    public function markAsPaid(): void
    {
        $this->is_paid = true;
        $this->paid_at = Carbon::now();
        $this->save();
    }

    /**
     * Mark this square as unpaid.
     */
    public function markAsUnpaid(): void
    {
        $this->is_paid = false;
        $this->paid_at = null;
        $this->save();
    }

    /**
     * Get the coordinate string for this square.
     */
    public function getCoordinatesAttribute(): string
    {
        return sprintf('(%d, %d)', $this->row, $this->col);
    }

    /**
     * Get the row number if revealed on the board.
     */
    public function getRowNumberAttribute(): ?int
    {
        $board = $this->board;

        if (! $board->numbers_revealed || $board->row_numbers === null) {
            return null;
        }

        return $board->row_numbers[$this->row] ?? null;
    }

    /**
     * Get the column number if revealed on the board.
     */
    public function getColNumberAttribute(): ?int
    {
        $board = $this->board;

        if (! $board->numbers_revealed || $board->col_numbers === null) {
            return null;
        }

        return $board->col_numbers[$this->col] ?? null;
    }
}
