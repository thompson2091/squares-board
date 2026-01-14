<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Display the platform admin dashboard.
     */
    public function index(): View
    {
        // User statistics
        $totalUsers = User::count();

        // Count users who have created boards
        $boardCreatorsCount = 0;
        if (class_exists(\App\Models\Board::class)) {
            $boardCreatorsCount = \App\Models\Board::distinct('owner_id')->count('owner_id');
        }

        // Role breakdown (we'll override board_admin with actual board creators)
        $usersByRole = [
            'player' => User::where('role', 'player')->count(),
            'board_admin' => $boardCreatorsCount,
            'platform_admin' => User::where('role', 'platform_admin')->count(),
        ];

        // Recent users
        $recentUsers = User::orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Board statistics
        $boardStats = [
            'total' => 0,
            'open' => 0,
            'locked' => 0,
            'completed' => 0,
        ];

        // Get board stats if the Board model exists
        if (class_exists(\App\Models\Board::class)) {
            /** @var class-string<\Illuminate\Database\Eloquent\Model> $boardClass */
            $boardClass = \App\Models\Board::class;
            $boardStats['total'] = $boardClass::count();
            $boardStats['open'] = $boardClass::where('status', 'open')->count();
            $boardStats['locked'] = $boardClass::where('status', 'locked')->count();
            $boardStats['completed'] = $boardClass::where('status', 'completed')->count();
        }

        return view('admin.dashboard', [
            'totalUsers' => $totalUsers,
            'usersByRole' => $usersByRole,
            'recentUsers' => $recentUsers,
            'boardStats' => $boardStats,
        ]);
    }
}
