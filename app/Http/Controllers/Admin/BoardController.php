<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Board;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    /**
     * Display a listing of all boards.
     */
    public function index(Request $request): View
    {
        $query = Board::with('owner')
            ->withCount(['squares as claimed_count' => function ($query): void {
                $query->whereNotNull('user_id');
            }]);

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('owner', function ($q) use ($search): void {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $boards = $query->orderByDesc('created_at')
            ->paginate(20);

        $statuses = [
            'draft' => 'Draft',
            'open' => 'Open',
            'locked' => 'Locked',
            'completed' => 'Completed',
        ];

        return view('admin.boards.index', [
            'boards' => $boards,
            'statuses' => $statuses,
        ]);
    }
}
