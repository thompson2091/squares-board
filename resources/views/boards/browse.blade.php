<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Browse Public Boards') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($boards->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                        </svg>
                        <h3 class="mt-2 text-sm font-semibold text-gray-900">{{ __('No public boards available') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Check back later for new boards to join.') }}</p>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($boards as $board)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-3">
                                    <h3 class="font-semibold text-lg text-gray-900 truncate">{{ $board->name }}</h3>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $board->status_color }}">
                                        {{ $board->status_display }}
                                    </span>
                                </div>

                                <div class="space-y-2 mb-4">
                                    <div class="flex items-center text-sm">
                                        <svg class="h-5 w-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span class="font-medium text-gray-900">{{ $board->team_row }}</span>
                                        <span class="mx-2 text-gray-400">vs</span>
                                        <span class="font-medium text-gray-900">{{ $board->team_col }}</span>
                                    </div>

                                    @if($board->game_date)
                                        <div class="flex items-center text-sm text-gray-600">
                                            <svg class="h-5 w-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ $board->game_date->format('M j, Y g:i A') }}
                                        </div>
                                    @endif

                                    @if($board->description)
                                        <p class="text-sm text-gray-600 line-clamp-2">{{ $board->description }}</p>
                                    @endif
                                </div>

                                <div class="border-t border-gray-100 pt-4">
                                    <div class="grid grid-cols-3 gap-4 text-center mb-4">
                                        <div>
                                            <div class="text-lg font-semibold text-gray-900">{{ $board->price_display }}</div>
                                            <div class="text-xs text-gray-500">{{ __('per square') }}</div>
                                        </div>
                                        <div>
                                            <div class="text-lg font-semibold text-gray-900">{{ $board->claimed_count }}/100</div>
                                            <div class="text-xs text-gray-500">{{ __('claimed') }}</div>
                                        </div>
                                        <div>
                                            <div class="text-lg font-semibold text-gray-900">{{ $board->max_squares_per_user }}</div>
                                            <div class="text-xs text-gray-500">{{ __('max/user') }}</div>
                                        </div>
                                    </div>

                                    {{-- Progress bar --}}
                                    <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                                        <div class="bg-indigo-600 h-2 rounded-full transition-all duration-300" style="width: {{ $board->claimed_count }}%"></div>
                                    </div>

                                    <a href="{{ route('boards.show', $board) }}" class="block w-full text-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 transition-colors">
                                        {{ __('View Board') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-6">
                    {{ $boards->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
