<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Game Scores: {{ $board->name }}
            </h2>
            <a href="{{ route('manage.boards.show', $board) }}" class="text-sm text-violet-600 hover:text-violet-800">
                Back to Management
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Numbers Check --}}
            @if(empty($board->row_numbers) || empty($board->col_numbers))
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-amber-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <p class="font-medium text-amber-800">Numbers Not Generated</p>
                            <p class="text-sm text-amber-700">Lock the board to generate numbers before entering scores.</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Team Header --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
                <div class="grid grid-cols-2 gap-4 text-center">
                    <div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Row Team</div>
                        <div class="text-lg font-bold text-gray-900">{{ $board->team_row }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Column Team</div>
                        <div class="text-lg font-bold text-gray-900">{{ $board->team_col }}</div>
                    </div>
                </div>
            </div>

            {{-- Score Cards Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @php
                    $quarterColors = [
                        'Q1' => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'accent' => 'bg-amber-500', 'text' => 'text-amber-700'],
                        'Q2' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'accent' => 'bg-emerald-500', 'text' => 'text-emerald-700'],
                        'Q3' => ['bg' => 'bg-sky-50', 'border' => 'border-sky-200', 'accent' => 'bg-sky-500', 'text' => 'text-sky-700'],
                        'final' => ['bg' => 'bg-violet-50', 'border' => 'border-violet-200', 'accent' => 'bg-violet-500', 'text' => 'text-violet-700'],
                    ];
                @endphp

                @foreach($quarters as $quarter)
                    @php
                        $existingScore = $gameScores->get($quarter);
                        $hasWinners = in_array($quarter, $quartersWithWinners, true);
                        $colors = $quarterColors[$quarter];
                        $label = \App\Models\GameScore::QUARTER_LABELS[$quarter] ?? $quarter;
                        $show2mw = in_array($quarter, $quartersWith2mw, true);
                    @endphp

                    <div class="bg-white rounded-lg shadow-sm border {{ $existingScore ? $colors['border'] : 'border-gray-200' }} overflow-hidden flex flex-col">
                        {{-- Card Header --}}
                        <div class="px-4 py-3 border-b {{ $existingScore ? $colors['bg'] . ' ' . $colors['border'] : 'bg-gray-50 border-gray-200' }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="w-2 h-2 rounded-full {{ $colors['accent'] }} mr-2"></span>
                                    <span class="font-semibold text-gray-900">{{ $label }}</span>
                                </div>
                                @if($existingScore)
                                    <div class="flex items-center text-xs {{ $colors['text'] }}">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        {{ $hasWinners ? 'Winners Calculated' : 'Recorded' }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Card Body --}}
                        <div class="p-4 flex-1 flex flex-col">
                            <form action="{{ route('manage.boards.scores.store', $board) }}" method="POST" class="flex-1 flex flex-col">
                                @csrf
                                <input type="hidden" name="quarter" value="{{ $quarter }}">

                                {{-- 2-Minute Warning Score (only if configured for this quarter) --}}
                                @if($show2mw)
                                    <div class="pb-3">
                                        <div class="text-xs font-medium text-amber-600 mb-2 text-center">2-Minute Warning Score</div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs text-gray-400 mb-1 text-center">{{ $board->team_row }}</label>
                                                <input type="number"
                                                       name="team_row_2mw_score"
                                                       id="team_row_2mw_score_{{ $quarter }}"
                                                       value="{{ $existingScore?->team_row_2mw_score }}"
                                                       min="0"
                                                       max="999"
                                                       placeholder="-"
                                                       class="block w-full text-center text-lg font-semibold rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 bg-amber-50"
                                                       {{ empty($board->row_numbers) || empty($board->col_numbers) ? 'disabled' : '' }}>
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-400 mb-1 text-center">{{ $board->team_col }}</label>
                                                <input type="number"
                                                       name="team_col_2mw_score"
                                                       id="team_col_2mw_score_{{ $quarter }}"
                                                       value="{{ $existingScore?->team_col_2mw_score }}"
                                                       min="0"
                                                       max="999"
                                                       placeholder="-"
                                                       class="block w-full text-center text-lg font-semibold rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 bg-amber-50"
                                                       {{ empty($board->row_numbers) || empty($board->col_numbers) ? 'disabled' : '' }}>
                                            </div>
                                        </div>
                                        @if($existingScore && $existingScore->has2mwScores())
                                            <div class="text-center text-xs text-amber-600 mt-2">
                                                2MW digits: <span class="font-mono font-bold">{{ $existingScore->row_2mw_digit }}-{{ $existingScore->col_2mw_digit }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                {{-- Spacer to push quarter score to bottom --}}
                                <div class="flex-1"></div>

                                {{-- Quarter End Score --}}
                                <div class="mt-auto">
                                    @if($show2mw)
                                        <hr class="border-gray-200 mb-4">
                                    @endif
                                    <div class="grid grid-cols-2 gap-4 mb-3">
                                        <div>
                                            <label for="team_row_score_{{ $quarter }}" class="block text-xs font-medium text-gray-500 mb-1">
                                                {{ $board->team_row }}
                                            </label>
                                            <input type="number"
                                                   name="team_row_score"
                                                   id="team_row_score_{{ $quarter }}"
                                                   value="{{ $existingScore?->team_row_score }}"
                                                   min="0"
                                                   max="999"
                                                   placeholder="0"
                                                   class="block w-full text-center text-2xl font-bold rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                                   {{ empty($board->row_numbers) || empty($board->col_numbers) ? 'disabled' : '' }}
                                                   required>
                                        </div>
                                        <div>
                                            <label for="team_col_score_{{ $quarter }}" class="block text-xs font-medium text-gray-500 mb-1">
                                                {{ $board->team_col }}
                                            </label>
                                            <input type="number"
                                                   name="team_col_score"
                                                   id="team_col_score_{{ $quarter }}"
                                                   value="{{ $existingScore?->team_col_score }}"
                                                   min="0"
                                                   max="999"
                                                   placeholder="0"
                                                   class="block w-full text-center text-2xl font-bold rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                                   {{ empty($board->row_numbers) || empty($board->col_numbers) ? 'disabled' : '' }}
                                                   required>
                                        </div>
                                    </div>

                                    @if($existingScore)
                                        <div class="text-center text-sm text-gray-500 mb-3">
                                            Winning digits: <span class="font-mono font-bold">{{ $existingScore->row_digit }}-{{ $existingScore->col_digit }}</span>
                                        </div>
                                    @endif

                                    <button type="submit"
                                            class="w-full px-4 py-2 text-sm font-medium rounded-md transition-colors
                                                {{ $existingScore
                                                    ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                                    : 'bg-violet-600 text-white hover:bg-violet-700' }}"
                                            {{ empty($board->row_numbers) || empty($board->col_numbers) ? 'disabled' : '' }}>
                                        {{ $existingScore ? 'Update Score' : 'Save Score' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Complete Board Section --}}
            @php
                // Check if all quarters have scores
                $allQuartersHaveScores = count(array_filter($quarters, fn($q) => $gameScores->has($q))) === count($quarters);

                // Check if 2MW scores are filled for quarters that need them
                $all2mwComplete = true;
                foreach ($quartersWith2mw as $quarter) {
                    $score = $gameScores->get($quarter);
                    if (!$score || $score->team_row_2mw_score === null || $score->team_col_2mw_score === null) {
                        $all2mwComplete = false;
                        break;
                    }
                }

                $canComplete = $allQuartersHaveScores && $all2mwComplete && $board->status !== \App\Models\Board::STATUS_COMPLETED;
                $isCompleted = $board->status === \App\Models\Board::STATUS_COMPLETED;
            @endphp

            @if($isCompleted)
                <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center justify-center">
                        <svg class="h-5 w-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-medium text-green-800">Board Completed</span>
                    </div>
                    <p class="text-sm text-green-700 text-center mt-1">All scores have been finalized and winners calculated.</p>
                </div>
            @elseif($canComplete)
                <div class="mt-6 bg-violet-50 border border-violet-200 rounded-lg p-4">
                    <div class="text-center">
                        <h3 class="font-semibold text-violet-900 mb-2">All Scores Entered</h3>
                        <p class="text-sm text-violet-700 mb-4">All quarter scores have been recorded. Ready to finalize the board?</p>
                        <form action="{{ route('manage.boards.scores.complete', $board) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit"
                                    class="px-6 py-3 bg-violet-600 text-white font-semibold rounded-lg hover:bg-violet-700 transition-colors">
                                Complete Board
                            </button>
                        </form>
                    </div>
                </div>
            @else
                @php
                    $missingQuarters = array_filter($quarters, fn($q) => !$gameScores->has($q));
                    $missing2mw = [];
                    foreach ($quartersWith2mw as $quarter) {
                        $score = $gameScores->get($quarter);
                        if ($score && ($score->team_row_2mw_score === null || $score->team_col_2mw_score === null)) {
                            $missing2mw[] = \App\Models\GameScore::QUARTER_LABELS[$quarter] ?? $quarter;
                        }
                    }
                @endphp
                <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="text-center text-sm text-gray-600">
                        @if(count($missingQuarters) > 0)
                            <p>Missing scores for: <span class="font-medium">{{ implode(', ', array_map(fn($q) => \App\Models\GameScore::QUARTER_LABELS[$q] ?? $q, $missingQuarters)) }}</span></p>
                        @endif
                        @if(count($missing2mw) > 0)
                            <p class="mt-1">Missing 2-minute warning scores for: <span class="font-medium">{{ implode(', ', $missing2mw) }}</span></p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Help Text --}}
            <div class="mt-6 text-center text-sm text-gray-500">
                <p>Enter scores at the end of each quarter. Winners are calculated based on the last digit of each score. Once all scores are entered, click "Complete Board" to finalize.</p>
            </div>
        </div>
    </div>
</x-app-layout>
