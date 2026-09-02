@props([
    'seasons',
    'title' => null,
    'empty' => null,
])

{{-- Season pools a user owns or plays in. Their weekly boards are filtered out
     of every board listing, so this is how people find their pool again. --}}
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">{{ $title ?? __('Your Season Pools') }}</h3>
            {{-- Gated the same way the Create Board button is, and hidden from
                 guests since this list also appears on the public browse page. --}}
            @auth
                @if(auth()->user()->canCreateBoards())
                    <a href="{{ route('seasons.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                        {{ __('New Season') }}
                    </a>
                @endif
            @endauth
        </div>

        @if($seasons->isEmpty())
            <p class="text-sm text-gray-500">
                {{ $empty ?? __('No season pools yet. A season runs one grid across many games - players claim a square once and keep it all year.') }}
            </p>
        @else
            <div class="space-y-2">
                @foreach($seasons as $season)
                    @php
                        // Weeks can exist before the season starts (the schedule
                        // gets loaded during signups), so go by status rather
                        // than by whether a current week is there.
                        $hasKickedOff = $season->isActive() || $season->isCompleted();
                        $currentWeek = $hasKickedOff ? $season->currentWeekNumber() : null;
                        $week = $currentWeek === null ? null : $season->week($currentWeek);
                    @endphp
                    <a href="{{ $season->url }}" class="flex items-center justify-between gap-4 p-3 rounded-lg border border-gray-100 hover:bg-gray-50 transition-colors">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-900 truncate">{{ $season->name }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wide {{ $season->status_color }}">
                                    {{ $season->status_display }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5 truncate">
                                @if($week)
                                    {{ __('Week :n of :total', ['n' => $week->number, 'total' => $season->total_weeks]) }}
                                    <span class="mx-1 text-gray-300">|</span>
                                    {{ $week->matchup }}
                                @else
                                    {{ __('Signups open') }}
                                    <span class="mx-1 text-gray-300">|</span>
                                    {{ __(':n weeks', ['n' => $season->total_weeks]) }}
                                @endif
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <div class="text-sm font-semibold text-gray-900">{{ $season->season_total_display }}</div>
                            <div class="text-[11px] text-gray-500">{{ $season->price_display }}/{{ __('wk') }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
