<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('My Boards') }}
            </h2>
            @if(auth()->user()->canCreateBoards())
                <a href="{{ route('boards.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Create Board') }}
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Owned Boards --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Boards I Own') }}</h3>

                    @if($ownedBoards->isEmpty())
                        <p class="text-gray-500">{{ __('You have not created any boards yet.') }}</p>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($ownedBoards as $board)
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-medium text-gray-900 truncate">{{ $board->name }}</h4>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $board->status_color }}">
                                            {{ $board->status_display }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2">
                                        {{ $board->team_row }} vs {{ $board->team_col }}
                                    </p>
                                    @if($board->game_date)
                                        <p class="text-xs text-gray-500 mb-2">
                                            {{ $board->game_date->format('M j, Y g:i A') }}
                                        </p>
                                    @endif
                                    <div class="flex justify-between items-center text-sm text-gray-500 mb-3">
                                        <span>{{ $board->claimedSquareCount() }}/100 squares</span>
                                        <span>{{ $board->price_display }}/sq</span>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="{{ route('boards.show', $board) }}" class="flex-1 text-center px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-md text-sm font-medium hover:bg-indigo-100 transition-colors">
                                            {{ __('View') }}
                                        </a>
                                        <a href="{{ route('boards.edit', $board) }}" class="flex-1 text-center px-3 py-1.5 bg-gray-50 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-100 transition-colors">
                                            {{ __('Edit') }}
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Co-Admin Boards --}}
            @if($adminBoards->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Boards I Help Manage') }}</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($adminBoards as $board)
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-medium text-gray-900 truncate">{{ $board->name }}</h4>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $board->status_color }}">
                                            {{ $board->status_display }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2">
                                        {{ $board->team_row }} vs {{ $board->team_col }}
                                    </p>
                                    <p class="text-xs text-gray-500 mb-3">
                                        {{ __('Owner:') }} {{ $board->owner->name }}
                                    </p>
                                    <a href="{{ route('boards.show', $board) }}" class="block text-center px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-md text-sm font-medium hover:bg-indigo-100 transition-colors">
                                        {{ __('View Board') }}
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Participating Boards --}}
            @if($participatingBoards->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Boards I\'m Playing') }}</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($participatingBoards as $board)
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-medium text-gray-900 truncate">{{ $board->name }}</h4>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $board->status_color }}">
                                            {{ $board->status_display }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2">
                                        {{ $board->team_row }} vs {{ $board->team_col }}
                                    </p>
                                    <div class="flex justify-between items-center text-sm text-gray-500 mb-3">
                                        <span>{{ $board->userSquareCount(auth()->user()) }} squares</span>
                                        <span>{{ $board->price_display }}/sq</span>
                                    </div>
                                    <a href="{{ route('boards.show', $board) }}" class="block text-center px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-md text-sm font-medium hover:bg-indigo-100 transition-colors">
                                        {{ __('View Board') }}
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Browse More Boards --}}
            <div class="text-center">
                <a href="{{ route('boards.browse') }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                    {{ __('Browse Public Boards') }} &rarr;
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
