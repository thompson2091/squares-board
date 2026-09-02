<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string|null $slug
 * @property int $owner_id
 * @property int|null $season_id
 * @property int|null $week_number
 * @property bool $is_roster
 * @property string $name
 * @property string|null $description
 * @property string $team_row
 * @property string $team_col
 * @property \Illuminate\Support\Carbon|null $game_date
 * @property int $price_per_square
 * @property int $max_squares_per_user
 * @property bool $is_public
 * @property string $status
 * @property array<int, int>|null $row_numbers
 * @property array<int, int>|null $col_numbers
 * @property bool $numbers_revealed
 * @property string|null $payment_instructions
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $owner
 * @property-read Season|null $season
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PayoutRule> $effectivePayoutRules
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Square> $squares
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $admins
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PayoutRule> $payoutRules
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Winner> $winners
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GameScore> $gameScores
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BoardAdmin> $boardAdmins
 */
class Board extends Model
{
    /** @use HasFactory<\Database\Factories\BoardFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'boards';

    /**
     * Board status constants.
     */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_OPEN = 'open';

    public const STATUS_LOCKED = 'locked';

    public const STATUS_COMPLETED = 'completed';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'slug',
        'owner_id',
        'season_id',
        'week_number',
        'is_roster',
        'name',
        'description',
        'team_row',
        'team_col',
        'game_date',
        'price_per_square',
        'max_squares_per_user',
        'is_public',
        'status',
        'row_numbers',
        'col_numbers',
        'numbers_revealed',
        'payment_instructions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'row_numbers' => 'array',
            'col_numbers' => 'array',
            'game_date' => 'datetime',
            'numbers_revealed' => 'boolean',
            'is_public' => 'boolean',
            'price_per_square' => 'integer',
            'max_squares_per_user' => 'integer',
            'is_roster' => 'boolean',
            'week_number' => 'integer',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Board $board): void {
            if (empty($board->uuid)) {
                $board->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Resolve the model for route binding (supports both UUID and slug).
     *
     * @param  mixed  $value
     * @param  string|null  $field
     */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        // If a specific field is requested, use standard binding
        if ($field !== null) {
            return $this->where($field, $value)->first();
        }

        // Check if value looks like a UUID (has dashes in UUID format)
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            return $this->where('uuid', $value)->first();
        }

        // Otherwise try slug first, then uuid as fallback
        return $this->where('slug', $value)->first()
            ?? $this->where('uuid', $value)->first();
    }

    /**
     * Get the URL for the board (prefers slug over uuid).
     */
    public function getUrlAttribute(): string
    {
        return $this->slug !== null
            ? url('/'.$this->slug)
            : route('boards.show', $this);
    }

    /**
     * Get payment instructions with links set to open in new tabs.
     */
    public function getPaymentInstructionsHtmlAttribute(): string
    {
        if ($this->payment_instructions === null) {
            return '';
        }

        // Add target="_blank" and rel="noopener noreferrer" to all links
        return (string) preg_replace(
            '/<a\s+href=/i',
            '<a target="_blank" rel="noopener noreferrer" href=',
            clean($this->payment_instructions)
        );
    }

    /**
     * Get the owner of the board.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the season this board belongs to, if any.
     *
     * @return BelongsTo<Season, $this>
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * Get all squares on this board.
     *
     * @return HasMany<Square, $this>
     */
    public function squares(): HasMany
    {
        return $this->hasMany(Square::class);
    }

    /**
     * Whether this board is a season's roster - the master list of who owns
     * which square for the whole season.
     */
    public function isRoster(): bool
    {
        return $this->is_roster === true;
    }

    /**
     * Whether this board is one week of a season.
     */
    public function isSeasonWeek(): bool
    {
        return $this->season_id !== null && ! $this->isRoster();
    }

    /**
     * The payout rules that actually apply to this board.
     *
     * A season's rules are shared by every week and stored once on the roster
     * board, so weekly boards never carry their own.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, PayoutRule>
     */
    public function getEffectivePayoutRulesAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->isSeasonWeek()) {
            return $this->payoutRules;
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, PayoutRule> $rules */
        $rules = $this->season?->roster()->payoutRules
            ?? new \Illuminate\Database\Eloquent\Collection;

        return $rules;
    }

    /**
     * Create this board's 100 squares.
     */
    public function seedSquares(): void
    {
        $squares = [];
        $now = now();

        for ($row = 0; $row < 10; $row++) {
            for ($col = 0; $col < 10; $col++) {
                $squares[] = [
                    'board_id' => $this->id,
                    'row' => $row,
                    'col' => $col,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        Square::insert($squares);
    }

    /**
     * Get the admin users for this board.
     *
     * @return BelongsToMany<User, $this>
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'board_admins')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get the payout rules for this board.
     *
     * @return HasMany<PayoutRule, $this>
     */
    public function payoutRules(): HasMany
    {
        return $this->hasMany(PayoutRule::class);
    }

    /**
     * Get the winners for this board.
     *
     * @return HasMany<Winner, $this>
     */
    public function winners(): HasMany
    {
        return $this->hasMany(Winner::class);
    }

    /**
     * Get the game scores for this board.
     *
     * @return HasMany<GameScore, $this>
     */
    public function gameScores(): HasMany
    {
        return $this->hasMany(GameScore::class);
    }

    /**
     * Get the board admin entries for this board.
     *
     * @return HasMany<BoardAdmin, $this>
     */
    public function boardAdmins(): HasMany
    {
        return $this->hasMany(BoardAdmin::class);
    }

    /**
     * Check if the board is full (all 100 squares claimed).
     */
    public function isFull(): bool
    {
        return $this->claimedSquareCount() >= 100;
    }

    /**
     * Check if a user can claim more squares on this board.
     */
    public function canUserClaim(User $user): bool
    {
        // Board must be open
        if ($this->status !== self::STATUS_OPEN) {
            return false;
        }

        // Check if user hasn't exceeded max squares
        return $this->userSquareCount($user) < $this->max_squares_per_user;
    }

    /**
     * Get the count of squares claimed by a user on this board.
     */
    public function userSquareCount(User $user): int
    {
        if ($this->relationLoaded('squares')) {
            return $this->squares->where('user_id', $user->id)->count();
        }

        return $this->squares()->where('user_id', $user->id)->count();
    }

    /**
     * Get the count of claimed squares on this board.
     */
    public function claimedSquareCount(): int
    {
        if ($this->relationLoaded('squares')) {
            return $this->squares->whereNotNull('user_id')->count();
        }

        return $this->squares()->whereNotNull('user_id')->count();
    }

    /**
     * Get the count of paid squares on this board.
     */
    public function paidSquareCount(): int
    {
        if ($this->relationLoaded('squares')) {
            return $this->squares->where('is_paid', true)->count();
        }

        return $this->squares()->where('is_paid', true)->count();
    }

    /**
     * Get the total pot amount in cents.
     */
    public function getTotalPotAttribute(): int
    {
        return $this->paidSquareCount() * $this->price_per_square;
    }

    /**
     * Get the price per square formatted as currency.
     */
    public function getPriceDisplayAttribute(): string
    {
        return '$'.number_format($this->price_per_square / 100, 2);
    }

    /**
     * Get the total pot formatted as currency.
     */
    public function getTotalPotDisplayAttribute(): string
    {
        return '$'.number_format($this->total_pot / 100, 2);
    }

    /**
     * Check if the board is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if the board is open for claiming.
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Check if the board is locked.
     */
    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    /**
     * Check if the board is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Get the status display name.
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_OPEN => 'Open',
            self::STATUS_LOCKED => 'Locked',
            self::STATUS_COMPLETED => 'Completed',
            default => 'Unknown',
        };
    }

    /**
     * Get the status badge color class.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'bg-gray-100 text-gray-800',
            self::STATUS_OPEN => 'bg-green-100 text-green-800',
            self::STATUS_LOCKED => 'bg-yellow-100 text-yellow-800',
            self::STATUS_COMPLETED => 'bg-blue-100 text-blue-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Check if a user is the owner or an admin of this board.
     */
    public function isAdminUser(User $user): bool
    {
        // Platform admins have access to all boards
        if ($user->isPlatformAdmin()) {
            return true;
        }

        if ($this->owner_id === $user->id) {
            return true;
        }

        return $this->admins()->where('user_id', $user->id)->exists();
    }

    /**
     * Generate random numbers for rows and columns.
     */
    public function generateNumbers(): void
    {
        $numbers = range(0, 9);

        shuffle($numbers);
        $this->row_numbers = $numbers;

        shuffle($numbers);
        $this->col_numbers = $numbers;

        $this->save();
    }

    /**
     * Get the square at a specific row and column.
     */
    public function getSquareAt(int $row, int $col): ?Square
    {
        return $this->squares()->where('row', $row)->where('col', $col)->first();
    }
}
