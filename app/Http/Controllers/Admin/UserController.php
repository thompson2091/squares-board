<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): View
    {
        $query = User::query();

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        // Filter by creator status
        if ($request->filled('creator_status')) {
            $creatorStatus = $request->input('creator_status');
            if ($creatorStatus === 'approved') {
                $query->where('is_approved_creator', true);
            } elseif ($creatorStatus === 'pending') {
                $query->where('is_approved_creator', false);
            }
        }

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('created_at')->paginate(20);

        return view('admin.users.index', [
            'users' => $users,
            'roles' => [
                User::ROLE_PLAYER => 'Player',
                User::ROLE_BOARD_ADMIN => 'Board Admin',
                User::ROLE_PLATFORM_ADMIN => 'Platform Admin',
            ],
        ]);
    }

    /**
     * Approve a user as a board creator.
     */
    public function approveCreator(User $user): RedirectResponse
    {
        $user->update(['is_approved_creator' => true]);

        return redirect()
            ->back()
            ->with('success', "User '{$user->name}' has been approved as a board creator.");
    }

    /**
     * Update a user's role.
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in([
                User::ROLE_PLAYER,
                User::ROLE_BOARD_ADMIN,
                User::ROLE_PLATFORM_ADMIN,
            ])],
        ]);

        $user->update(['role' => $validated['role']]);

        return redirect()
            ->back()
            ->with('success', "User '{$user->name}' role updated to '{$user->getRoleDisplayName()}'.");
    }
}
