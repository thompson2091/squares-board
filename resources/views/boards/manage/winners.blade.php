<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Winners: {{ $board->name }}
            </h2>
            <a href="{{ route('manage.boards.show', $board) }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                Back to Management
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

            <!-- Total Payouts Summary -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Payout Summary</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="text-sm text-green-600">Total Payouts</div>
                            <div class="text-3xl font-bold text-green-600">${{ number_format($totalPayouts / 100, 2) }}</div>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="text-sm text-blue-600">Total Winners</div>
                            <div class="text-3xl font-bold text-blue-600">{{ $winners->count() }}</div>
                        </div>
                        <div class="bg-purple-50 rounded-lg p-4">
                            <div class="text-sm text-purple-600">Unique Winners</div>
                            <div class="text-3xl font-bold text-purple-600">{{ $payoutsByUser->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payouts by User -->
            @if($payoutsByUser->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Payouts by User</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            User
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Wins
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Total Payout
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($payoutsByUser as $userData)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $userData['user']?->name ?? 'Unknown' }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ $userData['user']?->email ?? '' }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $userData['wins'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                                                ${{ number_format($userData['total'] / 100, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Winners by Quarter -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Winners by Quarter</h3>

                    @if($winners->isEmpty())
                        <p class="text-gray-500 text-center py-8">No winners have been calculated yet. Enter game scores to calculate winners.</p>
                    @else
                        @foreach($quarters as $quarter)
                            @php
                                $quarterWinners = $winnersByQuarter->get($quarter, collect());
                                $quarterScore = $gameScores->get($quarter);
                            @endphp

                            <div class="mb-6 last:mb-0">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-md font-medium text-gray-800">
                                        {{ $quarter === 'final' ? 'Final Score' : $quarter }}
                                    </h4>
                                    @if($quarterScore)
                                        <span class="text-sm text-gray-500">
                                            Score: {{ $quarterScore->team_row_score }} - {{ $quarterScore->team_col_score }}
                                            ({{ $quarterScore->row_digit }}-{{ $quarterScore->col_digit }})
                                        </span>
                                    @else
                                        <span class="text-sm text-gray-400">No score recorded</span>
                                    @endif
                                </div>

                                @if($quarterWinners->isEmpty())
                                    <p class="text-sm text-gray-400 italic">No winners for this quarter</p>
                                @else
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                                        Winner
                                                    </th>
                                                    <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                                        Square
                                                    </th>
                                                    <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                                        Type
                                                    </th>
                                                    <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                                        Payout
                                                    </th>
                                                    <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                                        Notes
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @foreach($quarterWinners as $winner)
                                                    <tr>
                                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                                            {{ $winner->user?->name ?? 'Unknown' }}
                                                        </td>
                                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                                            Row {{ ($winner->square?->row ?? 0) + 1 }}, Col {{ ($winner->square?->col ?? 0) + 1 }}
                                                            @if($board->numbers_revealed && $board->row_numbers && $board->col_numbers)
                                                                ({{ $board->team_row }} {{ $board->row_numbers[$winner->square?->row] ?? '?' }} - {{ $board->team_col }} {{ $board->col_numbers[$winner->square?->col] ?? '?' }})
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2 whitespace-nowrap">
                                                            @if($winner->is_2mw)
                                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                                                    2-Min Warning
                                                                </span>
                                                            @elseif($winner->is_touching)
                                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                    Touching
                                                                </span>
                                                            @elseif($winner->is_reverse)
                                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                                    Reverse
                                                                </span>
                                                            @else
                                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                    Primary
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-green-600">
                                                            {{ $winner->payout_display }}
                                                        </td>
                                                        <td class="px-4 py-2 text-sm text-gray-500">
                                                            {{ $winner->notes ?? '-' }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
