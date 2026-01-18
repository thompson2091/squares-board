@props(['board'])

@php
    $gameScores = $board->gameScores->sortBy(function ($score) {
        return array_search($score->quarter, ['Q1', 'Q2', 'Q3', 'final']);
    });

    // Get the latest score for the "current" display
    $latestScore = $gameScores->last();

    // Check if board has 2MW payout rules configured
    $has2mwPayouts = $board->payoutRules->contains('winner_type', '2mw');

    // Quarter display config
    $quarterConfig = [
        'Q1' => ['label' => 'Q1', 'short' => '1st'],
        'Q2' => ['label' => 'Q2', 'short' => 'Half'],
        'Q3' => ['label' => 'Q3', 'short' => '3rd'],
        'final' => ['label' => 'Final', 'short' => 'Final'],
    ];
@endphp

@if($gameScores->isNotEmpty())
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        {{-- Main Score Display --}}
        <div class="px-4 py-4 sm:px-6 bg-gradient-to-r from-indigo-50 to-purple-50">
            <div class="flex items-center justify-between">
                {{-- Row Team --}}
                <div class="flex-1 text-center">
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-1">
                        {{ $board->team_row }}
                    </div>
                    <div class="flex items-center justify-center gap-2">
                        <span class="text-4xl sm:text-5xl font-bold text-gray-900 tabular-nums">
                            {{ $latestScore->team_row_score }}
                        </span>
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 text-sm font-bold ring-2 ring-indigo-200">
                            {{ $latestScore->row_digit }}
                        </span>
                    </div>
                </div>

                {{-- Divider / Quarter Indicator --}}
                <div class="px-4 sm:px-6">
                    <div class="text-center">
                        @if($latestScore->is_final)
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Final</div>
                        @else
                            <div class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">
                                {{ $quarterConfig[$latestScore->quarter]['short'] ?? $latestScore->quarter }}
                            </div>
                        @endif
                        <div class="text-2xl font-light text-gray-300 my-1">-</div>
                    </div>
                </div>

                {{-- Column Team --}}
                <div class="flex-1 text-center">
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-1">
                        {{ $board->team_col }}
                    </div>
                    <div class="flex items-center justify-center gap-2">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 text-sm font-bold ring-2 ring-indigo-200">
                            {{ $latestScore->col_digit }}
                        </span>
                        <span class="text-4xl sm:text-5xl font-bold text-gray-900 tabular-nums">
                            {{ $latestScore->team_col_score }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quarter Breakdown --}}
        <div class="border-t border-gray-100 px-4 py-3 sm:px-6">
            <div class="flex items-center justify-center gap-2 sm:gap-4 flex-wrap">
                @foreach(['Q1', 'Q2', 'Q3', 'final'] as $quarter)
                    @php
                        $score = $gameScores->firstWhere('quarter', $quarter);
                        $config = $quarterConfig[$quarter];
                        $isActive = $latestScore && $latestScore->quarter === $quarter;
                        $supports2mw = in_array($quarter, ['Q2', 'final']);
                        $show2mw = $has2mwPayouts && $score && $score->has2mwScores() && $supports2mw;
                    @endphp
                    <div class="flex flex-col items-center gap-0.5 {{ $isActive ? 'opacity-100' : 'opacity-50' }}">
                        <div class="flex items-center gap-1.5 sm:gap-2">
                            <span class="text-xs font-medium {{ $isActive ? 'text-indigo-600' : 'text-gray-400' }} w-8 sm:w-10 text-right">
                                {{ $config['label'] }}:
                            </span>
                            @if($score)
                                <span class="text-sm font-semibold text-gray-900 tabular-nums">
                                    {{ $score->team_row_score }}-{{ $score->team_col_score }}
                                </span>
                                {{-- Show winning digits as small badges --}}
                                <span class="hidden sm:inline-flex items-center gap-0.5 text-xs">
                                    <span class="inline-flex items-center justify-center w-4 h-4 rounded bg-indigo-100 text-indigo-700 font-medium">
                                        {{ $score->row_digit }}
                                    </span>
                                    <span class="text-gray-300">-</span>
                                    <span class="inline-flex items-center justify-center w-4 h-4 rounded bg-indigo-100 text-indigo-700 font-medium">
                                        {{ $score->col_digit }}
                                    </span>
                                </span>
                            @else
                                <span class="text-sm text-gray-300 tabular-nums">--</span>
                            @endif
                        </div>
                        {{-- 2MW row - show for Q2/Final when configured, blank placeholder for Q1/Q3 --}}
                        @if($has2mwPayouts)
                            @if($show2mw)
                                <div class="flex items-center gap-1.5 sm:gap-2">
                                    <span class="text-xs font-medium text-amber-600 w-8 sm:w-10 text-right">2MW:</span>
                                    <span class="text-sm font-semibold text-gray-900 tabular-nums">
                                        {{ $score->team_row_2mw_score }}-{{ $score->team_col_2mw_score }}
                                    </span>
                                    <span class="hidden sm:inline-flex items-center gap-0.5 text-xs">
                                        <span class="inline-flex items-center justify-center w-4 h-4 rounded bg-amber-100 text-amber-600 font-medium">
                                            {{ $score->row_2mw_digit }}
                                        </span>
                                        <span class="text-gray-300">-</span>
                                        <span class="inline-flex items-center justify-center w-4 h-4 rounded bg-amber-100 text-amber-600 font-medium">
                                            {{ $score->col_2mw_digit }}
                                        </span>
                                    </span>
                                </div>
                            @elseif(!$supports2mw)
                                {{-- Blank placeholder for Q1/Q3 to maintain alignment --}}
                                <div class="h-5"></div>
                            @elseif($score && !$score->has2mwScores())
                                {{-- Q2/Final exists but no 2MW scores yet --}}
                                <div class="flex items-center gap-1.5 sm:gap-2">
                                    <span class="text-xs font-medium text-gray-300 w-8 sm:w-10 text-right">2MW:</span>
                                    <span class="text-sm text-gray-300 tabular-nums">--</span>
                                </div>
                            @else
                                {{-- Q2/Final has no score at all yet --}}
                                <div class="h-5"></div>
                            @endif
                        @endif
                    </div>
                    @if(!$loop->last)
                        <span class="text-gray-200 hidden sm:inline {{ $has2mwPayouts ? 'self-stretch flex items-center' : '' }}">|</span>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
@endif
