@props([
    'season',
    'week' => null,
    'isRoster' => false,
    'isStandings' => false,
    'mySquareCount' => 0,
    'myWinnings' => 0,
])

@php
    $weekNumber = $week?->number;
    $previousUrl = match (true) {
        $weekNumber === null => null,
        $weekNumber > 1 => $season->weekUrl($weekNumber - 1),
        default => $season->rosterUrl(),
    };
    $nextUrl = match (true) {
        $weekNumber === null => $season->weekUrl(1),
        $weekNumber < $season->total_weeks => $season->weekUrl($weekNumber + 1),
        default => null,
    };
    // Only the roster shows the claim count, so don't pay for it on week pages.
    $claimedCount = $isRoster ? $season->rosterBoard->claimedSquareCount() : null;

    // Organizers land here from a shared link like everyone else, so the season
    // dashboard needs a way in that isn't "go via one week's board settings".
    $viewer = auth()->user();
    $canManage = $viewer !== null && $season->isAdminUser($viewer);
@endphp

{{-- One slim strip carrying everything a season page needs above the grid:
     where you are, how to move between weeks, and what it costs.

     No overflow-hidden here - the week picker popover has to escape the bar. --}}
<div class="relative z-20 bg-white shadow-sm sm:rounded-lg">
    <div class="flex flex-wrap items-center gap-x-3 gap-y-2 px-3 py-2">
        {{-- Roster / week switcher --}}
        <div class="flex items-center gap-1">
            <a
                href="{{ $season->rosterUrl() }}"
                class="px-2.5 py-1.5 rounded-md text-sm font-semibold transition-colors {{ $isRoster ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}"
            >
                {{ __('Roster') }}
            </a>
            <a
                href="{{ $season->standingsUrl() }}"
                class="px-2.5 py-1.5 rounded-md text-sm font-semibold transition-colors {{ $isStandings ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}"
            >
                {{ __('Standings') }}
            </a>

            <span class="w-px h-5 bg-gray-200 mx-0.5"></span>

            @if($previousUrl)
                <a href="{{ $previousUrl }}" class="p-1.5 rounded-md text-gray-500 hover:bg-gray-100" aria-label="{{ __('Previous week') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
            @else
                <span class="p-1.5 text-gray-200"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg></span>
            @endif

            {{-- Week picker: 18 tabs is a lot of chrome, so they live in a popover --}}
            <div x-data="{ open: false }" class="relative z-30">
                <button
                    type="button"
                    @click="open = !open"
                    @click.outside="open = false"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-sm font-semibold text-gray-900 hover:bg-gray-100"
                >
                    @if($weekNumber === null)
                        {{ __('Jump to week') }}
                    @else
                        {{ __('Week :n of :total', ['n' => $weekNumber, 'total' => $season->total_weeks]) }}
                    @endif
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div
                    x-show="open"
                    x-cloak
                    x-transition.origin.top.left
                    class="absolute left-0 mt-1 z-50 w-80 bg-white rounded-lg shadow-xl ring-1 ring-black ring-opacity-5 p-2"
                >
                    <div class="grid grid-cols-3 gap-1">
                        @foreach($season->weeks as $seasonWeek)
                            @php
                                $isActive = $weekNumber === $seasonWeek->number;
                                if ($isActive) {
                                    $chipClass = 'bg-indigo-600 text-white';
                                } elseif ($seasonWeek->isCompleted()) {
                                    $chipClass = 'text-gray-400 hover:bg-gray-100';
                                } else {
                                    $chipClass = 'text-gray-700 hover:bg-gray-100';
                                }
                            @endphp
                            <a href="{{ $season->weekUrl($seasonWeek->number) }}" class="px-2 py-1.5 rounded-md {{ $chipClass }}">
                                <span class="flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wide {{ $isActive ? 'text-indigo-100' : 'text-gray-400' }}">
                                    {{ __('Wk :n', ['n' => $seasonWeek->number]) }}
                                    @if($seasonWeek->isCompleted())
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    @elseif($seasonWeek->isCurrent())
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                    @endif
                                </span>
                                <span class="block text-xs font-medium truncate">
                                    {{ $seasonWeek->has_matchup ? $seasonWeek->team_row.'/'.$seasonWeek->team_col : __('TBD') }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            @if($nextUrl)
                <a href="{{ $nextUrl }}" class="p-1.5 rounded-md text-gray-500 hover:bg-gray-100" aria-label="{{ __('Next week') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            @else
                <span class="p-1.5 text-gray-200"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg></span>
            @endif
        </div>

        {{-- What you're looking at --}}
        <div class="flex items-center gap-2 min-w-0 flex-1">
            @if($isRoster)
                <span class="text-sm font-semibold text-gray-900 truncate">
                    {{ __('One square, all :n weeks', ['n' => $season->total_weeks]) }}
                </span>
                <span class="hidden md:inline text-xs text-gray-500 truncate">
                    {{ __('It stays yours all season - new numbers are drawn every game.') }}
                </span>
            @elseif($isStandings)
                <span class="text-sm font-semibold text-gray-900 truncate">
                    {{ __('Season standings') }}
                </span>
                <span class="hidden md:inline text-xs text-gray-500 truncate">
                    {{ __('Every week counted together, best to worst.') }}
                </span>
            @else
                <span class="text-sm font-semibold text-gray-900 truncate">
                    @if($week?->has_matchup)
                        {{ $week->team_row }} <span class="text-gray-400 font-normal">vs</span> {{ $week->team_col }}
                    @else
                        {{ __('Matchup not set') }}
                    @endif
                </span>
                @if($week?->game_date)
                    <span class="hidden md:inline text-xs text-gray-500 whitespace-nowrap">
                        {{ $week->game_date->format('D M j, g:i A') }}
                    </span>
                @endif
                @if($week?->isCurrent())
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-green-100 text-green-700 text-[10px] font-semibold uppercase tracking-wide">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>{{ __('Live') }}
                    </span>
                @elseif($week?->isCompleted())
                    <span class="px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500 text-[10px] font-semibold uppercase tracking-wide">{{ __('Final') }}</span>
                @endif
            @endif
        </div>

        {{-- Money --}}
        <div class="flex items-center gap-3 text-sm whitespace-nowrap ml-auto">
            @if($isRoster)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-900">
                    <span class="font-semibold">{{ $season->price_display }}</span>
                    <span class="text-indigo-400 text-xs">&times;</span>
                    <span class="font-semibold">{{ $season->total_weeks }}</span>
                    <span class="text-indigo-400 text-xs">=</span>
                    <span class="font-bold">{{ $season->season_total_display }}</span>
                </span>
                <span class="text-gray-500 text-xs">{{ __(':n/100 claimed', ['n' => $claimedCount]) }}</span>
            @else
                <span class="text-gray-500 text-xs">
                    {{ $season->price_display }}/wk
                    <span class="text-gray-300 mx-1">|</span>
                    {{ $season->season_total_display }} {{ __('season') }}
                </span>
                @if($mySquareCount > 0)
                    <span class="text-gray-700">
                        <span class="font-semibold">{{ $mySquareCount }}</span>
                        <span class="text-gray-500 text-xs">{{ __('squares') }}</span>
                    </span>
                @endif
                @if($myWinnings > 0)
                    <span class="text-green-600 font-semibold">${{ number_format($myWinnings / 100, 2) }}</span>
                @endif
            @endif

            @if($canManage)
                <a
                    href="{{ route('manage.seasons.index', $season) }}"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-gray-900 text-white text-xs font-semibold hover:bg-gray-700 transition-colors"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ __('Manage') }}
                </a>
            @endif
        </div>
    </div>

    {{-- Season progress: one segment per week --}}
    <div class="flex gap-px px-3 pb-2">
        @foreach($season->weeks as $seasonWeek)
            @php
                if ($seasonWeek->isCompleted()) {
                    $segmentClass = 'bg-indigo-400';
                } elseif ($seasonWeek->isCurrent()) {
                    $segmentClass = 'bg-green-400';
                } else {
                    $segmentClass = 'bg-gray-200';
                }
            @endphp
            <div
                class="h-1 flex-1 rounded-full {{ $segmentClass }} {{ $weekNumber === $seasonWeek->number ? 'ring-2 ring-indigo-600' : '' }}"
                title="{{ __('Week :n', ['n' => $seasonWeek->number]) }} - {{ $seasonWeek->matchup }}"
            ></div>
        @endforeach
    </div>
</div>
