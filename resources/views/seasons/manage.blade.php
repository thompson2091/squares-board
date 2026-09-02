<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $season->name }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ __('Manage season') }}
                    <span class="mx-2">|</span>
                    {{ __(':n weeks', ['n' => $season->total_weeks]) }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $season->status_color }}">
                    {{ $season->status_display }}
                </span>
                <a href="{{ $season->rosterUrl() }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 transition-colors">
                    {{ __('View Roster') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="sm:px-6 lg:px-8 space-y-4">
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 sm:rounded-lg">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 sm:rounded-lg">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 sm:rounded-lg">{{ $errors->first() }}</div>
            @endif

            {{-- Season at a glance, plus the screens that stay season-wide --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="flex flex-wrap items-center gap-x-6 gap-y-3 px-4 py-3">
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">{{ __('Roster') }}</div>
                        <div class="text-sm text-gray-900">
                            <span class="font-semibold">{{ $claimedCount }}</span><span class="text-gray-400">/100</span>
                            <span class="text-gray-500 text-xs ml-1">{{ __('claimed') }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">{{ __('Paid') }}</div>
                        <div class="text-sm text-gray-900">
                            <span class="font-semibold">{{ $paidCount }}</span><span class="text-gray-400">/100</span>
                            <span class="text-gray-500 text-xs ml-1">{{ __('for the season') }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">{{ __('Weekly Pot') }}</div>
                        <div class="text-sm font-semibold text-gray-900">${{ number_format($claimedCount * $season->price_per_square / 100, 2) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">{{ __('Number Draw') }}</div>
                        <div class="text-sm text-gray-900">
                            {{ $season->drawsUpfront() ? __('All weeks drawn up front') : __('Drawn each week') }}
                        </div>
                    </div>

                    {{-- Payouts, payments and co-admins are all season-wide, so
                         they point at the roster board's existing screens. --}}
                    <div class="flex items-center gap-2 ml-auto">
                        @php
                            $roster = $season->rosterBoard;
                            $seasonLinks = $roster === null ? [] : [
                                __('Payouts') => route('manage.boards.payouts.index', $roster),
                                __('Payments') => route('manage.boards.payments.index', $roster),
                                __('Co-Admins') => route('manage.boards.admins.index', $roster),
                                __('Settings') => route('seasons.edit', $season),
                            ];
                        @endphp
                        @foreach($seasonLinks as $label => $url)
                            <a href="{{ $url }}" class="px-2.5 py-1.5 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-100">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Payout rules are shared by every week, so they're stated once --}}
            <div class="bg-amber-50 border border-amber-200 sm:rounded-lg px-4 py-2.5 flex flex-wrap items-center gap-x-3 gap-y-1">
                <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm text-amber-900">
                    {{ __('One set of payout rules runs every week:') }}
                </span>
                @forelse($payoutRules as $rule)
                    <span class="text-xs px-2 py-0.5 rounded-full bg-white text-amber-900 border border-amber-200">
                        {{ $rule->quarter_label }} {{ $rule->winner_type_label }} &middot; {{ $rule->amount_display }}
                    </span>
                @empty
                    <span class="text-sm font-medium text-amber-800">{{ __('none set yet') }}</span>
                @endforelse
                @if($season->rosterBoard)
                    <a href="{{ route('manage.boards.payouts.index', $season->rosterBoard) }}" class="text-sm font-medium text-amber-700 hover:text-amber-900 ml-auto">
                        {{ __('Edit payouts') }}
                    </a>
                @endif
            </div>

            {{-- Before kickoff: start the season and create the weekly boards.
                 Keyed on status, not on whether weeks exist - the schedule can
                 be loaded first, while signups are still open. --}}
            @if(! $season->isActive() && ! $season->isCompleted())
                <div class="bg-violet-50 border border-violet-200 sm:rounded-lg p-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-violet-900">{{ __('Start the season') }}</h3>
                            <p class="text-sm text-violet-700 mt-1">
                                @if($season->hasStarted())
                                    {{ __('Copies the roster onto all :n weeks and closes signups.', ['n' => $season->total_weeks]) }}
                                @else
                                    {{ __('Creates :n weekly boards, copies the roster onto each, and closes signups.', ['n' => $season->total_weeks]) }}
                                @endif
                                @if($season->drawsUpfront())
                                    {{ __('Numbers are drawn for every week straight away, and stay hidden until you reveal each one.') }}
                                @else
                                    {{ __("You'll draw each week's numbers yourself before its game.") }}
                                @endif
                            </p>
                            @if($claimedCount < 100)
                                <p class="text-sm text-violet-900 mt-2 font-medium">
                                    {{ __(':n squares are still unclaimed. They stay empty all season and can never win - the weekly pot is :pot.', [
                                        'n' => 100 - $claimedCount,
                                        'pot' => '$'.number_format($claimedCount * $season->price_per_square / 100, 2),
                                    ]) }}
                                </p>
                            @endif
                        </div>
                        <div class="flex-shrink-0 flex items-center gap-2">
                            {{-- Load the schedule without closing signups, so the
                                 matchups are up before anyone has to claim blind. --}}
                            @if(! $season->hasStarted())
                                <form method="POST" action="{{ route('manage.seasons.weeks.create', $season) }}">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-white border border-violet-300 text-violet-700 rounded-md text-sm font-medium hover:bg-violet-100 transition-colors">
                                        {{ __('Add Schedule First') }}
                                    </button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('manage.seasons.start', $season) }}"
                                  onsubmit="return confirm('Start the season? This closes signups for good.');">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-violet-600 text-white rounded-md text-sm font-medium hover:bg-violet-700 transition-colors">
                                    {{ __('Start Season') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            {{-- The weeks. Fill in what you know; blanks stay TBD. --}}
            @if($season->hasStarted())
            <form method="POST" action="{{ route('manage.seasons.weeks.update', $season) }}">
                @csrf
                @method('PATCH')
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">{{ __('Weekly Matchups') }}</h3>
                            <p class="text-sm text-gray-500 mt-0.5">
                                {{ __("Fill in the teams whenever you know them - a week only needs its matchup before you enter scores.") }}
                            </p>
                        </div>
                        <span class="text-sm text-gray-500">
                            {{ __(':set of :total set', ['set' => $matchupsSet, 'total' => $season->total_weeks]) }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    @foreach([__('Wk'), __('Row Team'), __('Column Team'), __('Kickoff'), __('Numbers'), __('Scores'), ''] as $heading)
                                        <th class="px-3 py-2 text-left text-[10px] font-semibold uppercase tracking-wide text-gray-500 whitespace-nowrap">
                                            {{ $heading }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($season->weeks as $week)
                                    @php
                                        $weekBoard = $season->weekBoard($week->number);
                                        $latestScore = $weekBoard?->gameScores->last();
                                        $rowClass = match (true) {
                                            $week->isCurrent() => 'bg-green-50/60',
                                            $week->isCompleted() => 'bg-gray-50/60',
                                            default => '',
                                        };
                                    @endphp
                                    <tr class="{{ $rowClass }}">
                                        {{-- Week number --}}
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-sm font-semibold text-gray-900">{{ $week->number }}</span>
                                                @if($week->isCurrent())
                                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500" title="{{ __('This week') }}"></span>
                                                @elseif($week->isCompleted())
                                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- Teams --}}
                                        <td class="px-3 py-2">
                                            <input
                                                type="text"
                                                name="weeks[{{ $week->number }}][team_row]"
                                                value="{{ $week->team_row }}"
                                                placeholder="{{ __('TBD') }}"
                                                class="block w-32 text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 placeholder:text-gray-300"
                                            />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input
                                                type="text"
                                                name="weeks[{{ $week->number }}][team_col]"
                                                value="{{ $week->team_col }}"
                                                placeholder="{{ __('TBD') }}"
                                                class="block w-32 text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 placeholder:text-gray-300"
                                            />
                                        </td>

                                        {{-- Kickoff --}}
                                        <td class="px-3 py-2">
                                            <input
                                                type="datetime-local"
                                                name="weeks[{{ $week->number }}][game_date]"
                                                value="{{ $week->game_date?->format('Y-m-d\TH:i') }}"
                                                class="block w-48 text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                        </td>

                                        {{-- Numbers --}}
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            @if($week->numbers_revealed)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-xs font-medium">{{ __('Revealed') }}</span>
                                            @elseif($season->drawsUpfront())
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs font-medium">{{ __('Drawn, hidden') }}</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800 text-xs font-medium">{{ __('Not drawn') }}</span>
                                            @endif
                                        </td>

                                        {{-- Scores --}}
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            @if($latestScore)
                                                <span class="text-sm text-gray-900 tabular-nums">
                                                    {{ $latestScore->team_row_score }}-{{ $latestScore->team_col_score }}
                                                </span>
                                                <span class="text-xs text-gray-500 ml-1">
                                                    {{ $latestScore->quarter === 'final' ? __('Final') : $latestScore->quarter }}
                                                </span>
                                            @else
                                                <span class="text-sm text-gray-300">&mdash;</span>
                                            @endif
                                        </td>

                                        {{-- Per-week actions. Reveal posts through a form
                                             rendered outside this one - forms can't nest. --}}
                                        <td class="px-3 py-2 whitespace-nowrap text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                @if(! $week->numbers_revealed)
                                                    <button
                                                        type="submit"
                                                        form="reveal-week-{{ $week->number }}"
                                                        class="px-2 py-1 rounded-md text-xs font-medium text-indigo-600 hover:bg-indigo-50"
                                                    >
                                                        {{ __('Reveal') }}
                                                    </button>
                                                @endif
                                                @if($weekBoard)
                                                    <a href="{{ route('manage.boards.scores.index', $weekBoard) }}" class="px-2 py-1 rounded-md text-xs font-medium text-gray-600 hover:bg-gray-100">{{ __('Scores') }}</a>
                                                @endif
                                                <a href="{{ $season->weekUrl($week->number) }}" class="px-2 py-1 rounded-md text-xs font-medium text-gray-600 hover:bg-gray-100">{{ __('View') }}</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between gap-4">
                        <p class="text-sm text-gray-500">
                            {{ __('Blank weeks stay TBD. Players still see their squares.') }}
                        </p>
                        <x-primary-button>{{ __('Save All Weeks') }}</x-primary-button>
                    </div>
                </div>
            </form>

            {{-- Reveal targets for the buttons in the table above --}}
            @foreach($season->weeks as $week)
                @if(! $week->numbers_revealed)
                    <form id="reveal-week-{{ $week->number }}" method="POST" action="{{ route('manage.seasons.weeks.reveal', [$season, $week->number]) }}" class="hidden">
                        @csrf
                    </form>
                @endif
            @endforeach
            @endif
        </div>
    </div>
</x-app-layout>
