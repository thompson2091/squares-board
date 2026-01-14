<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Manage Board: {{ $board->name }}
            </h2>
            <a href="{{ route('boards.show', $board) }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                View Board
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Board Full Notice --}}
            @if(($claimedCount ?? 0) >= 100 && $board->isOpen())
                <div class="mb-6 bg-violet-50 border border-violet-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-violet-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="font-medium text-violet-900">All 100 squares have been claimed!</p>
                                <p class="text-sm text-violet-700">You can now lock the board and reveal the numbers.</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('boards.lock', $board) }}" onsubmit="return confirm('Are you sure you want to lock this board? This will reveal numbers and prevent further changes.');">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-violet-600 text-white rounded-md text-sm font-medium hover:bg-violet-700 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                Lock Board & Reveal Numbers
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Board Stats -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Board Overview</h3>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-500">Total Squares</div>
                            <div class="text-2xl font-bold text-gray-900">100</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-500">Claimed</div>
                            <div class="text-2xl font-bold text-gray-900">{{ $claimedCount ?? 0 }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-500">Price Per Square</div>
                            <div class="text-2xl font-bold text-gray-900">${{ number_format(($board->price_per_square ?? 0) / 100, 2) }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-500">Total Pot</div>
                            <div class="text-2xl font-bold text-gray-900">${{ number_format((($claimedCount ?? 0) * ($board->price_per_square ?? 0)) / 100, 2) }}</div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="text-sm text-green-600">Collected</div>
                            <div class="text-2xl font-bold text-green-600">${{ number_format((($paidCount ?? 0) * ($board->price_per_square ?? 0)) / 100, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Management Links -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Payments -->
                <a href="{{ route('manage.boards.payments.index', $board) }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Payment Tracking</h3>
                                <p class="text-sm text-gray-500">Track who has paid for their squares</p>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Payout Rules -->
                <a href="{{ route('manage.boards.payouts.index', $board) }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Payout Rules</h3>
                                <p class="text-sm text-gray-500">Configure payouts for each quarter</p>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Scores -->
                <a href="{{ route('manage.boards.scores.index', $board) }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Game Scores</h3>
                                <p class="text-sm text-gray-500">Enter scores for each quarter</p>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Winners -->
                <a href="{{ route('manage.boards.winners.index', $board) }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                                <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Winners</h3>
                                <p class="text-sm text-gray-500">View calculated winners</p>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Co-Admins -->
                <a href="{{ route('manage.boards.admins.index', $board) }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-indigo-100 rounded-lg p-3">
                                <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Co-Admins</h3>
                                <p class="text-sm text-gray-500">Manage board administrators</p>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Board Settings -->
                <a href="{{ route('boards.edit', $board) }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-gray-100 rounded-lg p-3">
                                <svg class="h-6 w-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Board Settings</h3>
                                <p class="text-sm text-gray-500">Edit board details and configuration</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Board Settings Summary -->
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Board Settings</h3>
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Board Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $board->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $board->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($board->status ?? 'draft') }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Row Team</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $board->team_row ?? 'Not Set' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Column Team</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $board->team_col ?? 'Not Set' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Numbers Generated</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if(!empty($board->row_numbers) && !empty($board->col_numbers))
                                    Yes
                                @else
                                    No
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $board->created_at?->format('M j, Y g:i A') ?? 'Unknown' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
