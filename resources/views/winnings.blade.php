<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Your Winnings') }}
            </h2>
            <a href="{{ route('dashboard') }}"
               class="text-sm text-primary hover:text-primary-dark">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            {{-- Total Summary --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-center">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Total Winnings</h3>
                    <span class="text-4xl font-bold text-success">${{ number_format($totalWinnings / 100, 2) }}</span>
                </div>
            </div>

            {{-- Winnings List --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Winnings History</h3>

                    @if($winnings->isEmpty())
                        <p class="text-gray-500 text-center py-8">No winnings yet.</p>
                    @else
                        <div class="space-y-2">
                            @foreach($winnings as $winning)
                                @php
                                    $quarterLabel = $winning->quarter === 'final' ? 'Q4' : $winning->quarter;
                                    if ($winning->is_2mw) {
                                        $badgeColor = 'bg-amber-100 text-amber-800';
                                        $hoverText = $quarterLabel . ' 2-Minute Warning';
                                    } elseif ($winning->is_touching) {
                                        $badgeColor = 'bg-blue-100 text-blue-800';
                                        $hoverText = $quarterLabel . ' Touching';
                                    } elseif ($winning->is_reverse) {
                                        $badgeColor = 'bg-purple-100 text-purple-800';
                                        $hoverText = $quarterLabel . ' Reverse';
                                    } else {
                                        $badgeColor = 'bg-green-100 text-green-800';
                                        $hoverText = $quarterLabel . ' Primary';
                                    }
                                @endphp
                                <div class="flex items-center gap-3 p-3 rounded border {{ $loop->odd ? 'bg-gray-50' : 'bg-white' }}">
                                    {{-- Badge --}}
                                    <span class="inline-flex items-center justify-center px-2 py-1 rounded text-xs font-semibold {{ $badgeColor }} min-w-[40px] cursor-default" title="{{ $hoverText }}">{{ $quarterLabel }}</span>

                                    {{-- Name & Board --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-900 truncate">{{ $winning->square?->displayNameForSquare ?? $winning->user->name }}</div>
                                        <a href="{{ route('boards.show', $winning->board) }}" class="text-xs text-gray-500 hover:text-primary truncate block">{{ $winning->board->name ?? 'Unknown Board' }}</a>
                                        <div class="text-xs text-gray-400">{{ $winning->created_at->format('F jS, Y') }}</div>
                                    </div>

                                    {{-- Amount --}}
                                    <span class="text-lg font-semibold text-success whitespace-nowrap">${{ number_format($winning->payout_amount / 100, 2) }}</span>
                                </div>
                            @endforeach
                        </div>

                        {{-- Pagination --}}
                        <div class="mt-6">
                            {{ $winnings->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
