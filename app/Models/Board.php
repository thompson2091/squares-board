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
 * @property int $owner_id
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
        'owner_id',
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
     * Get the owner of the board.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
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
        return $this->squares()->whereNotNull('user_id')->count() >= 100;
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
        return $this->squares()->where('user_id', $user->id)->count();
    }

    /**
     * Get the count of claimed squares on this board.
     */
    public function claimedSquareCount(): int
    {
        return $this->squares()->whereNotNull('user_id')->count();
    }

    /**
     * Get the count of paid squares on this board.
     */
    public function paidSquareCount(): int
    {
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
