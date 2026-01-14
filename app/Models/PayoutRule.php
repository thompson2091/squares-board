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
 * @property string $payout_type
 * @property int $amount
 * @property string $winner_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Board $board
 *
 * @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<PayoutRule>>
 */
class PayoutRule extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<PayoutRule>> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payout_rules';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'board_id',
        'quarter',
        'payout_type',
        'amount',
        'winner_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    /**
     * Valid quarters for payout rules.
     *
     * @var list<string>
     */
    public const QUARTERS = ['Q1', 'Q2', 'Q3', 'final'];

    /**
     * Valid payout types.
     *
     * @var list<string>
     */
    public const PAYOUT_TYPES = ['percentage', 'fixed'];

    /**
     * Valid winner types.
     *
     * @var list<string>
     */
    public const WINNER_TYPES = ['primary', 'reverse', 'touching', '2mw'];

    /**
     * Winner types only valid for specific quarters.
     *
     * @var array<string, list<string>>
     */
    public const WINNER_TYPE_QUARTERS = [
        '2mw' => ['Q2', 'final'],
    ];

    /**
     * Human-readable labels for winner types.
     *
     * @var array<string, string>
     */
    public const WINNER_TYPE_LABELS = [
        'primary' => 'Primary Winner',
        'reverse' => 'Reverse Winner',
        'touching' => 'Touching Squares',
        '2mw' => '2-Min Warning',
    ];

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
     * Get the board that owns this payout rule.
     *
     * @return BelongsTo<Board, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * Calculate the actual payout amount in cents for a given pot total.
     */
    public function calculatePayout(int $potTotalCents): int
    {
        if ($this->payout_type === 'fixed') {
            return $this->amount;
        }

        // Percentage is stored as 0-10000 (0-100.00%)
        return (int) round($potTotalCents * $this->amount / 10000);
    }

    /**
     * Get the percentage as a human-readable string.
     */
    public function getPercentageDisplayAttribute(): string
    {
        if ($this->payout_type !== 'percentage') {
            return '';
        }

        return number_format($this->amount / 100, 2).'%';
    }

    /**
     * Get the amount as a human-readable currency string.
     */
    public function getAmountDisplayAttribute(): string
    {
        if ($this->payout_type === 'percentage') {
            return $this->getPercentageDisplayAttribute();
        }

        return '$'.number_format($this->amount / 100, 2);
    }

    /**
     * Get the quarter label for display.
     */
    public function getQuarterLabelAttribute(): string
    {
        return self::QUARTER_LABELS[$this->quarter] ?? $this->quarter;
    }

    /**
     * Get the winner type label for display.
     */
    public function getWinnerTypeLabelAttribute(): string
    {
        return self::WINNER_TYPE_LABELS[$this->winner_type] ?? ucfirst($this->winner_type);
    }
}
