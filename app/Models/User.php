<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role
 * @property bool $is_approved_creator
 * @property string|null $phone
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use Notifiable;

    public const ROLE_PLAYER = 'player';

    public const ROLE_BOARD_ADMIN = 'board_admin';

    public const ROLE_PLATFORM_ADMIN = 'platform_admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_approved_creator',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_approved_creator' => 'boolean',
        ];
    }

    /**
     * Get all boards owned by this user.
     *
     * @return HasMany<\App\Models\Board, $this>
     */
    public function ownedBoards(): HasMany
    {
        return $this->hasMany(Board::class, 'owner_id');
    }

    /**
     * Get all squares claimed by this user.
     *
     * @return HasMany<\App\Models\Square, $this>
     */
    public function squares(): HasMany
    {
        return $this->hasMany(Square::class, 'user_id');
    }

    /**
     * Get all winnings for this user.
     *
     * @return HasMany<\App\Models\Winner, $this>
     */
    public function winnings(): HasMany
    {
        return $this->hasMany(Winner::class, 'user_id');
    }

    /**
     * Check if the user is a platform administrator.
     */
    public function isPlatformAdmin(): bool
    {
        return $this->role === self::ROLE_PLATFORM_ADMIN;
    }

    /**
     * Check if the user is an approved creator (can create boards).
     */
    public function isApprovedCreator(): bool
    {
        return $this->is_approved_creator;
    }

    /**
     * Check if the user can create boards.
     * Platform admins can always create boards, others need approval.
     */
    public function canCreateBoards(): bool
    {
        return $this->isPlatformAdmin() || $this->isApprovedCreator();
    }

    /**
     * Check if the user is a board admin.
     */
    public function isBoardAdmin(): bool
    {
        return $this->role === self::ROLE_BOARD_ADMIN;
    }

    /**
     * Check if the user is a regular player.
     */
    public function isPlayer(): bool
    {
        return $this->role === self::ROLE_PLAYER;
    }

    /**
     * Get the display name for the user's role.
     */
    public function getRoleDisplayName(): string
    {
        return match ($this->role) {
            self::ROLE_PLATFORM_ADMIN => 'Platform Admin',
            self::ROLE_BOARD_ADMIN => 'Board Admin',
            self::ROLE_PLAYER => 'Player',
            default => 'Unknown',
        };
    }
}
