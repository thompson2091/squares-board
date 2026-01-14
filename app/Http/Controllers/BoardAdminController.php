<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardAdmin;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BoardAdminController extends Controller
{
    /**
     * Display the co-admin management page.
     */
    public function index(Board $board): View
    {
        $this->authorize('manage', $board);

        $admins = $board->boardAdmins()
            ->with('user')
            ->orderBy('created_at')
            ->get();

        return view('boards.manage.admins', [
            'board' => $board,
            'admins' => $admins,
        ]);
    }

    /**
     * Add a new co-admin to the board.
     */
    public function store(Request $request, Board $board): RedirectResponse
    {
        $this->authorize('manage', $board);

        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        // Find the user by email
        /** @var User|null $user */
        $user = User::where('email', $validated['email'])->first();

        if ($user === null) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'No user found with this email address.']);
        }

        // Check if user is the board owner
        if ($user->id === $board->owner_id) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'The board owner cannot be added as a co-admin.']);
        }

        // Check if user is already a co-admin
        $exists = $board->boardAdmins()
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'This user is already a co-admin of this board.']);
        }

        $board->boardAdmins()->create([
            'user_id' => $user->id,
            'role' => BoardAdmin::ROLE_ADMIN,
        ]);

        return back()->with('success', "Added {$user->name} as a co-admin.");
    }

    /**
     * Remove a co-admin from the board.
     */
    public function destroy(Board $board, BoardAdmin $admin): RedirectResponse
    {
        $this->authorize('manage', $board);

        // Ensure the admin belongs to this board
        if ($admin->board_id !== $board->id) {
            abort(404);
        }

        $userName = $admin->user->name ?? 'Unknown';

        $admin->delete();

        return back()->with('success', "Removed {$userName} as a co-admin.");
    }
}
