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
                                <div class="p-3 rounded border {{ $loop->odd ? 'bg-gray-50' : 'bg-white' }}">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <a href="{{ route('boards.show', $winning->board) }}" class="text-gray-900 font-medium hover:text-primary">
                                                {{ $winning->board->name ?? 'Unknown Board' }}
                                            </a>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-xs text-gray-500">{{ $winning->quarter === 'final' ? 'Final' : $winning->quarter }}</span>
                                                <span class="text-gray-300">·</span>
                                                @if($winning->is_2mw)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">2MW</span>
                                                @elseif($winning->is_touching)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Touching</span>
                                                @elseif($winning->is_reverse)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Reverse</span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Primary</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-400 mt-1">
                                                {{ $winning->created_at->format('F jS, Y') }}
                                            </div>
                                        </div>
                                        <span class="text-lg font-semibold text-success">${{ number_format($winning->payout_amount / 100, 2) }}</span>
                                    </div>
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
