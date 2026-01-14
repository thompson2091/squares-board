<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dashboard') }}
            </h2>
            <a href="{{ route('boards.create') }}"
               class="inline-flex items-center px-4 py-2 bg-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-dark focus:bg-primary-dark active:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Create Board
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Main Content (2 columns on large screens) --}}
                <div class="lg:col-span-2 space-y-6">
                    {{-- Boards You've Joined --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Boards You've Joined</h3>
                                <a href="{{ route('boards.index') }}" class="text-sm text-primary hover:text-primary-dark">
                                    Browse boards
                                </a>
                            </div>

                            @if($participatedBoards->isEmpty())
                                <div class="text-center py-8">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <h4 class="mt-2 text-sm font-medium text-gray-900">No boards joined yet</h4>
                                    <p class="mt-1 text-sm text-gray-500">Browse public boards or use an invite link to join a game.</p>
                                    <div class="mt-4">
                                        <a href="{{ route('boards.index') }}"
                                           class="inline-flex items-center px-4 py-2 bg-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-dark">
                                            Browse Boards
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="space-y-3">
                                    @foreach($participatedBoards as $board)
                                        <a href="{{ route('boards.show', $board) }}"
                                           class="block p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                                            <div class="flex justify-between items-center">
                                                <div>
                                                    <h4 class="font-medium text-gray-900">{{ $board->name }}</h4>
                                                    <p class="text-sm text-gray-500 mt-0.5">
                                                        ${{ number_format($board->price_per_square / 100, 2) }}/sq
                                                        @if($board->game_date)
                                                            <span class="text-gray-300 mx-1">·</span>
                                                            {{ $board->game_date->format('F jS, Y') }}
                                                        @endif
                                                    </p>
                                                </div>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    @if($board->status === 'open') bg-green-100 text-green-800
                                                    @elseif($board->status === 'locked') bg-yellow-100 text-yellow-800
                                                    @elseif($board->status === 'completed') bg-blue-100 text-blue-800
                                                    @else bg-gray-100 text-gray-800 @endif">
                                                    {{ ucfirst($board->status) }}
                                                </span>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Boards You Own --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Your Boards</h3>
                                <a href="{{ route('boards.create') }}" class="text-sm text-primary hover:text-primary-dark">
                                    View all
                                </a>
                            </div>

                            @if($ownedBoards->isEmpty())
                                <div class="text-center py-8">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                                    </svg>
                                    <h4 class="mt-2 text-sm font-medium text-gray-900">No boards yet</h4>
                                    <p class="mt-1 text-sm text-gray-500">Get started by creating your first board.</p>
                                    <div class="mt-4">
                                        <a href="{{ route('boards.create') }}"
                                           class="inline-flex items-center px-4 py-2 bg-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-dark">
                                            Create Board
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="space-y-3">
                                    @foreach($ownedBoards as $board)
                                        <a href="{{ route('boards.show', $board) }}"
                                           class="block p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <h4 class="font-medium text-gray-900">{{ $board->name }}</h4>
                                                    <p class="text-sm text-gray-500">
                                                        {{ $board->claimed_count ?? 0 }} / 100 squares claimed
                                                    </p>
                                                </div>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    @if($board->status === 'open') bg-green-100 text-green-800
                                                    @elseif($board->status === 'locked') bg-yellow-100 text-yellow-800
                                                    @elseif($board->status === 'completed') bg-blue-100 text-blue-800
                                                    @else bg-gray-100 text-gray-800 @endif">
                                                    {{ ucfirst($board->status) }}
                                                </span>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Sidebar --}}
                <div class="space-y-6">
                    {{-- Platform Admin Link --}}
                    @if($user->isPlatformAdmin())
                        <div class="bg-primary overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-white mb-2">Admin Panel</h3>
                                <p class="text-white text-opacity-80 text-sm mb-4">
                                    Access platform administration tools.
                                </p>
                                <a href="{{ route('admin.dashboard') }}"
                                   class="inline-flex items-center px-4 py-2 bg-white rounded-md font-semibold text-xs text-primary uppercase tracking-widest hover:bg-gray-100 transition">
                                    Open Admin Panel
                                    <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endif

                    {{-- Winnings Summary --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Your Winnings</h3>
                            <div class="text-center mb-4">
                                <span class="text-3xl font-bold text-success">${{ number_format($totalWinnings / 100, 2) }}</span>
                            </div>
                            <hr class="mb-4">

                            @if($recentWinnings->isNotEmpty())
                                <div class="space-y-1">
                                    @foreach($recentWinnings as $winning)
                                        <div class="text-sm p-2 rounded {{ $loop->odd ? 'bg-gray-50' : 'bg-white' }}">
                                            <a href="{{ route('boards.show', $winning->board) }}" class="text-gray-900 font-medium hover:text-primary">{{ $winning->board->name ?? 'Unknown Board' }}</a>
                                            <div class="flex justify-between items-center mt-0.5">
                                                <div class="flex items-center gap-1">
                                                    <span class="text-xs text-gray-500">{{ $winning->quarter === 'final' ? 'Final' : $winning->quarter }}</span>
                                                    <span class="text-gray-300">·</span>
                                                    @if($winning->is_2mw)
                                                        <span class="text-xs text-amber-600">2MW</span>
                                                    @elseif($winning->is_touching)
                                                        <span class="text-xs text-blue-600">Touching</span>
                                                    @elseif($winning->is_reverse)
                                                        <span class="text-xs text-purple-600">Reverse</span>
                                                    @else
                                                        <span class="text-xs text-green-600">Primary</span>
                                                    @endif
                                                </div>
                                                <span class="font-medium text-success">${{ number_format($winning->payout_amount / 100, 2) }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500">No winnings yet.</p>
                            @endif

                            <div class="text-center mt-4">
                                <a href="{{ route('winnings') }}" class="text-sm text-primary hover:text-primary-dark">View all winnings</a>
                            </div>
                        </div>
                    </div>

                    {{-- Quick Stats --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Stats</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Boards Owned</span>
                                    <span class="font-semibold">{{ $ownedBoards->count() }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Boards Joined</span>
                                    <span class="font-semibold">{{ $participatedBoards->count() }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Account Type</span>
                                    <span class="font-semibold">{{ $user->getRoleDisplayName() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
