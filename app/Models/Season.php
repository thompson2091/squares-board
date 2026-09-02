<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\SeasonWeek;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * A pool that runs one grid across many games.
 *
 * Players claim a square once on the roster board and keep that position for
 * every week; each weekly board draws its own numbers against its own matchup.
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $slug
 * @property int $owner_id
 * @property string $name
 * @property string|null $description
 * @property int $total_weeks
 * @property string $number_draw_mode
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $owner
 * @property-read Board|null $rosterBoard
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Board> $weekBoards
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Board> $boards
 * @property-read Collection<int, SeasonWeek> $weeks
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PayoutRule> $payoutRules
 * @property-read int $price_per_square
 * @property-read int $max_squares_per_user
 * @property-read bool $is_public
 * @property-read string|null $payment_instructions
 * @property-read int $season_total
 * @property-read string $price_display
 * @property-read string $season_total_display
 * @property-read string $status_display
 * @property-read string $status_color
 */
class Season extends Model
{
    /** @use HasFactory<\Database\Factories\SeasonFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'seasons';

    /**
     * Season status constants.
     */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_OPEN = 'open';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    /**
     * When the weekly numbers get drawn.
     */
    public const DRAW_UPFRONT = 'upfront';

    public const DRAW_MANUAL = 'manual';

    /** @var list<string> */
    public const DRAW_MODES = [self::DRAW_UPFRONT, self::DRAW_MANUAL];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'slug',
        'owner_id',
        'name',
        'description',
        'total_weeks',
        'number_draw_mode',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_weeks' => 'integer',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Season $season): void {
            if (empty($season->uuid)) {
                $season->uuid = (string) Str::uuid();
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
        if ($field !== null) {
            return $this->where($field, $value)->first();
        }

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', (string) $value)) {
            return $this->where('uuid', $value)->first();
        }

        return $this->where('slug', $value)->first()
            ?? $this->where('uuid', $value)->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Every board in this season - the roster plus each week.
     *
     * @return HasMany<Board, $this>
     */
    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }

    /**
     * The roster board: who owns which square for the whole season.
     *
     * @return HasOne<Board, $this>
     */
    public function rosterBoard(): HasOne
    {
        return $this->hasOne(Board::class)->where('is_roster', true);
    }

    /**
     * The weekly boards, in week order.
     *
     * @return HasMany<Board, $this>
     */
    public function weekBoards(): HasMany
    {
        return $this->hasMany(Board::class)
            ->where('is_roster', false)
            ->orderBy('week_number');
    }

    /**
     * The roster board, or null before it exists.
     *
     * A hasOne can always come back empty, but the relation property is typed
     * non-nullable, so funnel access through here. Honours an eager-loaded
     * relation rather than re-querying.
     */
    public function roster(): ?Board
    {
        if ($this->relationLoaded('rosterBoard')) {
            $loaded = $this->getRelation('rosterBoard');

            return $loaded instanceof Board ? $loaded : null;
        }

        return $this->rosterBoard()->first();
    }

    /**
     * The season's payout rules, which live on the roster board and are shared
     * by every week.
     *
     * Deliberately an accessor rather than a relation method: Eloquent would
     * throw on property access if a method named payoutRules() returned
     * anything other than a Relation.
     *
     * @return EloquentCollection<int, PayoutRule>
     */
    public function getPayoutRulesAttribute(): EloquentCollection
    {
        // No nullsafe needed before ??: property reads on null are suppressed
        // by its isset semantics. (Method calls still need ?->.)
        /** @var EloquentCollection<int, PayoutRule> $rules */
        $rules = $this->roster()->payoutRules ?? new EloquentCollection;

        return $rules;
    }

    /**
     * Seasons a user owns, co-admins, or holds a roster square in.
     *
     * Weekly boards are filtered out of every board listing, so this is how a
     * player finds their pool again without the original link.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Season>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Season>
     */
    public function scopeForUser(\Illuminate\Database\Eloquent\Builder $query, User $user): \Illuminate\Database\Eloquent\Builder
    {
        return $query
            ->where(function (\Illuminate\Database\Eloquent\Builder $query) use ($user): void {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('rosterBoard.squares', fn ($squares) => $squares->where('user_id', $user->id))
                    ->orWhereHas('rosterBoard.admins', fn ($admins) => $admins->where('user_id', $user->id));
            })
            ->with(['rosterBoard', 'weekBoards'])
            ->orderByDesc('created_at');
    }

    /**
     * Public seasons still taking signups.
     *
     * Weekly boards are filtered out of the browse listing, and the roster
     * board goes with them, so seasons need their own way to be discovered.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Season>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Season>
     */
    public function scopePubliclyOpen(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query
            ->whereIn('status', [self::STATUS_OPEN, self::STATUS_ACTIVE])
            ->whereHas('rosterBoard', fn ($board) => $board->where('is_public', true))
            ->with(['rosterBoard', 'weekBoards'])
            ->orderByDesc('created_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Settings that live on the roster board
    |--------------------------------------------------------------------------
    */

    public function getPricePerSquareAttribute(): int
    {
        return $this->roster()->price_per_square ?? 0;
    }

    public function getMaxSquaresPerUserAttribute(): int
    {
        return $this->roster()->max_squares_per_user ?? 0;
    }

    public function getIsPublicAttribute(): bool
    {
        return (bool) ($this->roster()->is_public ?? false);
    }

    public function getPaymentInstructionsAttribute(): ?string
    {
        return $this->roster()?->payment_instructions;
    }

    /*
    |--------------------------------------------------------------------------
    | Display
    |--------------------------------------------------------------------------
    */

    /**
     * Cost of one square for the whole season, in cents.
     */
    public function getSeasonTotalAttribute(): int
    {
        return $this->price_per_square * $this->total_weeks;
    }

    public function getPriceDisplayAttribute(): string
    {
        return '$'.number_format($this->price_per_square / 100, 2);
    }

    public function getSeasonTotalDisplayAttribute(): string
    {
        return '$'.number_format($this->season_total / 100, 2);
    }

    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_OPEN => 'Signups Open',
            self::STATUS_ACTIVE => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            default => 'Unknown',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'bg-gray-100 text-gray-800',
            self::STATUS_OPEN => 'bg-green-100 text-green-800',
            self::STATUS_ACTIVE => 'bg-yellow-100 text-yellow-800',
            self::STATUS_COMPLETED => 'bg-blue-100 text-blue-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Whether every week's numbers are drawn as soon as the season starts.
     */
    public function drawsUpfront(): bool
    {
        return $this->number_draw_mode === self::DRAW_UPFRONT;
    }

    /**
     * Whether the season has been started (weekly boards exist).
     */
    public function hasStarted(): bool
    {
        return $this->weekBoards()->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Weeks
    |--------------------------------------------------------------------------
    */

    /**
     * Every week of the season, as read views over the weekly boards.
     *
     * @return Collection<int, SeasonWeek>
     */
    public function getWeeksAttribute(): Collection
    {
        $currentWeek = $this->currentWeekNumber();

        return $this->weekBoards
            ->map(fn (Board $board): SeasonWeek => SeasonWeek::fromBoard($board, $currentWeek))
            ->values();
    }

    public function week(int $number): ?SeasonWeek
    {
        return $this->weeks->firstWhere('number', $number);
    }

    public function weekBoard(int $number): ?Board
    {
        return $this->weekBoards->firstWhere('week_number', $number);
    }

    /**
     * Which week the season URL should land on.
     *
     * The nearest upcoming kickoff, else the most recent one that has passed,
     * else the lowest-numbered week that isn't finished yet.
     */
    public function currentWeekNumber(): ?int
    {
        $boards = $this->weekBoards;

        if ($boards->isEmpty()) {
            return null;
        }

        $now = now();

        // The next kickoff on the calendar.
        $upcoming = $boards
            ->filter(fn (Board $board): bool => $board->game_date !== null && $board->game_date->gte($now))
            ->sortBy('game_date')
            ->first();

        if ($upcoming !== null) {
            return (int) $upcoming->week_number;
        }

        // Nothing scheduled ahead. Move on to the first week still to be
        // played, so an undated week 3 doesn't leave the season URL stuck on a
        // finished week 2 - kickoff times get filled in as the schedule firms
        // up, so later weeks are routinely dateless.
        $next = $boards->first(fn (Board $board): bool => ! $board->isCompleted()
            && ($board->game_date === null || $board->game_date->gte($now)));

        if ($next !== null) {
            return (int) $next->week_number;
        }

        // Every week is behind us: show the most recent.
        $played = $boards
            ->filter(fn (Board $board): bool => $board->game_date !== null && $board->game_date->lt($now))
            ->sortByDesc('game_date')
            ->first();

        if ($played !== null) {
            return (int) $played->week_number;
        }

        // Safe to dereference: an empty collection returned early above.
        $fallback = $boards->first(fn (Board $board): bool => ! $board->isCompleted())
            ?? $boards->first();

        return (int) $fallback->week_number;
    }

    /**
     * The board the season URL should show.
     */
    public function currentWeekBoard(): ?Board
    {
        $number = $this->currentWeekNumber();

        return $number === null ? null : $this->weekBoard($number);
    }

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    */

    /**
     * The short, shareable season URL, which always shows the current week.
     */
    public function getUrlAttribute(): string
    {
        return $this->slug !== null
            ? url('/'.$this->slug)
            : route('seasons.show', $this);
    }

    public function rosterUrl(): string
    {
        return $this->slug !== null
            ? url('/'.$this->slug.'/roster')
            : route('seasons.roster', $this);
    }

    public function weekUrl(int $number): string
    {
        return $this->slug !== null
            ? url('/'.$this->slug.'/week/'.$number)
            : route('seasons.week', [$this, $number]);
    }

    public function standingsUrl(): string
    {
        return $this->slug !== null
            ? url('/'.$this->slug.'/standings')
            : route('seasons.standings', $this);
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    /**
     * Season admins are the roster board's admins, so co-admins carry over.
     */
    public function isAdminUser(User $user): bool
    {
        return $this->roster()?->isAdminUser($user) ?? ($this->owner_id === $user->id);
    }
}
