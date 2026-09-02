@push('meta')
    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $season->name }} - {{ $season->total_weeks }} weeks of squares">
    <meta property="og:description" content="Claim one square and keep it all season. {{ $season->season_total_display }} for all {{ $season->total_weeks }} weeks, with new numbers drawn every game.">
    <meta property="og:image" content="{{ url('/og-image.svg') }}">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $season->name }} - {{ $season->total_weeks }} weeks of squares">
    <meta name="twitter:description" content="Claim one square and keep it all season. {{ $season->season_total_display }} for all {{ $season->total_weeks }} weeks, with new numbers drawn every game.">
    <meta name="twitter:image" content="{{ url('/og-image.svg') }}">
@endpush

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $season->name }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ __('Season roster') }}
                    <span class="mx-2">|</span>
                    {{ __(':n weeks', ['n' => $season->total_weeks]) }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $season->status_color }}">
                    {{ $season->status_display }}
                </span>
                {{-- The roster prints as a signup sheet: names only, no numbers --}}
                <a
                    href="{{ route('boards.print', $board) }}"
                    target="_blank"
                    class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 transition-colors"
                    title="{{ __('Print Roster') }}"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    {{ __('Print') }}
                </a>
                <button
                    type="button"
                    x-data="{ copied: false }"
                    @click="navigator.clipboard.writeText('{{ url()->current() }}'); copied = true; setTimeout(() => copied = false, 1500)"
                    class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 transition-colors"
                    :class="{ 'text-green-600': copied }"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                    <span x-text="copied ? '{{ __('Copied!') }}' : '{{ __('Share') }}'"></span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="sm:px-6 lg:px-8 space-y-4">
            <x-season.bar :season="$season" :is-roster="true" />

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                {{-- Roster grid: numbers are never drawn here, only ownership --}}
                <div class="lg:col-span-3 space-y-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-4 sm:p-6">
                            <x-board.grid
                                :board="$board"
                                :grid="$grid"
                                :user-squares="$userSquares"
                                :can-claim="$canClaim"
                                :is-guest="$isGuest"
                                :board-is-open="$boardIsOpen"
                                :is-admin="$isAdmin"
                                :show-team-labels="false"
                            />
                        </div>
                    </div>
                </div>

                {{-- Sidebar --}}
                <div class="space-y-6">
                    {{-- How a season pool works --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ __('How This Works') }}</h3>
                            <ol class="space-y-3">
                                @foreach([
                                    ['1', __('Claim your square'), __('Pick any open square. It stays yours for the whole season.')],
                                    ['2', __('Pay once'), __(':total covers all :n weeks - no weekly collecting.', ['total' => $season->season_total_display, 'n' => $season->total_weeks])],
                                    ['3', __('New numbers every week'), __('Your square keeps its spot, but the row and column numbers are redrawn for every game.')],
                                    ['4', __('Win week by week'), __('Each week pays out on its own. Check back after every game.')],
                                ] as [$step, $title, $copy])
                                    <li class="flex gap-3">
                                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center">{{ $step }}</span>
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium text-gray-900">{{ $title }}</div>
                                            <div class="text-xs text-gray-500 mt-0.5">{{ $copy }}</div>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </div>

                    {{-- Payment Instructions --}}
                    @if($board->payment_instructions)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-4">
                                <h3 class="text-sm font-semibold text-gray-900 mb-2">{{ __('Payment & Payout Info') }}</h3>
                                <div class="text-sm text-gray-600 payment-instructions prose prose-sm max-w-none">{!! $board->payment_instructions_html !!}</div>
                            </div>
                        </div>
                    @endif

                    {{-- Pot and payouts. These are per week - the same rules run
                         every game, so one set of numbers covers the season. --}}
                    <div>
                        <div class="px-1 pb-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                            {{ __('Every week') }}
                        </div>
                        <x-board.stats :board="$board" />
                    </div>

                    {{-- User's Squares --}}
                    @auth
                        @php $mySquares = $board->squares->where('user_id', auth()->id()); @endphp
                        @if($mySquares->isNotEmpty())
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="p-4">
                                    <div class="flex items-baseline justify-between mb-3">
                                        <h3 class="text-sm font-semibold text-gray-900">{{ __('Your Squares') }}</h3>
                                        <span class="text-xs text-gray-500">
                                            {{ __(':amount for the season', ['amount' => '$'.number_format($mySquares->count() * $season->season_total / 100, 2)]) }}
                                        </span>
                                    </div>
                                    <div class="space-y-2">
                                        @foreach($mySquares as $square)
                                            <div class="flex justify-between items-center text-sm">
                                                <span class="text-gray-600">
                                                    {{ __('Row :row, Col :col', ['row' => $square->row + 1, 'col' => $square->col + 1]) }}
                                                    @if($square->display_name)
                                                        <span class="text-gray-400">&middot; {{ $square->display_name }}</span>
                                                    @endif
                                                </span>
                                                @if($square->is_paid)
                                                    <span class="text-green-600 text-xs">{{ __('Paid') }}</span>
                                                @else
                                                    <span class="text-yellow-600 text-xs">{{ __('Unpaid') }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                    <p class="text-xs text-gray-400 mt-3 pt-3 border-t border-gray-100">
                                        {{ __('These positions are yours for every week. Numbers are drawn separately for each game.') }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    @endauth

                    <x-board.legend />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
