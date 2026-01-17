@push('meta')
    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $board->name }} - {{ $board->team_row }} vs {{ $board->team_col }}">
    <meta property="og:description" content="Join the squares pool for {{ $board->team_row }} vs {{ $board->team_col }}. Claim your squares and win big on game day!">
    <meta property="og:image" content="{{ url('/og-image.svg') }}">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $board->name }} - {{ $board->team_row }} vs {{ $board->team_col }}">
    <meta name="twitter:description" content="Join the squares pool for {{ $board->team_row }} vs {{ $board->team_col }}. Claim your squares and win big on game day!">
    <meta name="twitter:image" content="{{ url('/og-image.svg') }}">
@endpush

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $board->name }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $board->team_row }} vs {{ $board->team_col }}
                    @if($board->game_date)
                        <span class="mx-2">|</span>
                        {{ $board->game_date->format('M j, Y g:i A') }}
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $board->status_color }}">
                    {{ $board->status_display }}
                </span>
                {{-- Share Dropdown --}}
                <div x-data="{ open: false, copied: false }" class="relative">
                    <button
                        type="button"
                        @click="open = !open"
                        @click.outside="open = false"
                        class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 transition-colors"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                        </svg>
                        {{ __('Share') }}
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div
                        x-show="open"
                        x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50"
                    >
                        <div class="py-1">
                            {{-- Copy Link --}}
                            <button
                                type="button"
                                @click="
                                    navigator.clipboard.writeText('{{ url()->current() }}');
                                    copied = true;
                                    setTimeout(() => { copied = false; open = false; }, 1500);
                                "
                                class="w-full flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                :class="{ 'text-green-600': copied }"
                            >
                                <svg x-show="!copied" class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                </svg>
                                <svg x-show="copied" x-cloak class="w-4 h-4 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span x-text="copied ? '{{ __('Copied!') }}' : '{{ __('Copy Link') }}'"></span>
                            </button>

                            <div class="border-t border-gray-100 my-1"></div>

                            {{-- Twitter/X --}}
                            <a
                                href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($board->name . ' - ' . $board->team_row . ' vs ' . $board->team_col . '. Join my squares pool!') }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            >
                                <svg class="w-4 h-4 mr-3 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                </svg>
                                {{ __('Share on X') }}
                            </a>

                            {{-- Facebook --}}
                            <a
                                href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            >
                                <svg class="w-4 h-4 mr-3 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                                {{ __('Share on Facebook') }}
                            </a>

                            {{-- LinkedIn --}}
                            <a
                                href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(url()->current()) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            >
                                <svg class="w-4 h-4 mr-3 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                </svg>
                                {{ __('Share on LinkedIn') }}
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Donate Dropdown --}}
                @php
                    $donateOptions = collect([
                        'cashapp' => [
                            'value' => config('donate.cashapp'),
                            'label' => 'Cash App',
                            'url' => config('donate.cashapp') ? 'https://cash.app/' . ltrim(config('donate.cashapp'), '$') : null,
                            'icon' => '<path d="M23.59 3.47A5.1 5.1 0 0 0 20.54.42C19.23.12 18.04.01 16.67 0h-9.34C5.96.01 4.77.12 3.46.42A5.07 5.07 0 0 0 .41 3.47C.11 4.78 0 5.97 0 7.34v9.32c.01 1.37.11 2.56.41 3.87a5.1 5.1 0 0 0 3.05 3.05c1.31.3 2.5.41 3.87.42h9.34c1.37-.01 2.56-.11 3.87-.42a5.1 5.1 0 0 0 3.05-3.05c.3-1.31.41-2.5.42-3.87V7.34c-.01-1.37-.11-2.56-.42-3.87zM17.42 16.5l-1.75 1.75c-.17.17-.39.25-.62.25s-.45-.08-.62-.25l-1.19-1.19a4.73 4.73 0 0 1-2.12.5 4.75 4.75 0 0 1 0-9.5c.74 0 1.45.17 2.12.5l1.19-1.19a.88.88 0 0 1 1.24 0l1.75 1.75a.88.88 0 0 1 0 1.24l-1.19 1.19c.33.67.5 1.38.5 2.12s-.17 1.45-.5 2.12l1.19 1.19a.88.88 0 0 1 0 1.24v.02zM11.12 10.56a1.56 1.56 0 1 0 0 3.12 1.56 1.56 0 0 0 0-3.12z"/>',
                        ],
                        'venmo' => [
                            'value' => config('donate.venmo'),
                            'label' => 'Venmo',
                            'url' => config('donate.venmo') ? 'https://venmo.com/' . config('donate.venmo') : null,
                            'icon' => '<path d="M19.5 0h-15A4.5 4.5 0 0 0 0 4.5v15A4.5 4.5 0 0 0 4.5 24h15a4.5 4.5 0 0 0 4.5-4.5v-15A4.5 4.5 0 0 0 19.5 0zm-1.41 6.62c0 2.55-2.18 6.2-3.94 8.66H9.83L8.1 6.08l3.63-.35.89 7.16c.83-1.35 1.86-3.48 1.86-4.94 0-.76-.13-1.28-.3-1.69l3.45-.67c.3.6.46 1.37.46 2.03z"/>',
                        ],
                        'paypal' => [
                            'value' => config('donate.paypal'),
                            'label' => 'PayPal',
                            'url' => config('donate.paypal') ? 'https://paypal.me/' . config('donate.paypal') : null,
                            'icon' => '<path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.288-.077.437-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.106zm14.146-14.42a3.35 3.35 0 0 0-.607-.541c-.013.076-.026.175-.041.254-.93 4.778-4.005 7.201-9.138 7.201h-2.19a.563.563 0 0 0-.556.479l-1.187 7.527h-.506l-.24 1.516a.56.56 0 0 0 .554.647h3.882c.46 0 .85-.334.922-.788.06-.26.76-4.852.816-5.09a.932.932 0 0 1 .923-.788h.58c3.76 0 6.705-1.528 7.565-5.946.36-1.847.174-3.388-.777-4.471z"/>',
                        ],
                        'zelle' => [
                            'value' => config('donate.zelle'),
                            'label' => 'Zelle',
                            'url' => null,
                            'copyValue' => config('donate.zelle'),
                            'icon' => '<path d="M13.559 24h-3.118c-.678 0-1.229-.551-1.229-1.229V14.9H4.59c-.678 0-1.229-.551-1.229-1.229v-3.118c0-.519.322-.981.808-1.161L14.4 5.541V1.229C14.4.551 14.951 0 15.629 0h3.118c.678 0 1.229.551 1.229 1.229v7.871h4.622c.678 0 1.229.551 1.229 1.229v3.118c0 .519-.322.981-.808 1.161L9.6 18.459v4.312c0 .678-.551 1.229-1.229 1.229h-3.118c-.678 0-1.229-.551-1.229-1.229z"/>',
                        ],
                        'credit_card' => [
                            'value' => config('donate.credit_card'),
                            'label' => 'Credit Card',
                            'url' => config('donate.credit_card'),
                            'icon' => '<path d="M0 4a2 2 0 0 1 2-2h20a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2 0v3h20V4H2zm0 5v11h20V9H2zm2 4h4v2H4v-2zm6 0h8v2h-8v-2z"/>',
                        ],
                    ])->filter(fn($option) => !empty($option['value']));
                @endphp

                @if($donateOptions->isNotEmpty())
                    <div x-data="{ open: false, copied: false, copiedValue: '' }" class="relative">
                        <button
                            type="button"
                            @click="open = !open"
                            @click.outside="open = false"
                            class="inline-flex items-center px-3 py-1.5 bg-emerald-100 text-emerald-700 rounded-md text-sm font-medium hover:bg-emerald-200 transition-colors"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                            {{ __('Donate') }}
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            x-cloak
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50"
                        >
                            <div class="py-1">
                                @foreach($donateOptions as $key => $option)
                                    @if(!empty($option['url']))
                                        <a
                                            href="{{ $option['url'] }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                        >
                                            <svg class="w-4 h-4 mr-3 text-gray-400" fill="currentColor" viewBox="0 0 24 24">{!! $option['icon'] !!}</svg>
                                            {{ $option['label'] }}
                                        </a>
                                    @elseif(!empty($option['copyValue']))
                                        <button
                                            type="button"
                                            @click="
                                                navigator.clipboard.writeText('{{ $option['copyValue'] }}');
                                                copied = true;
                                                copiedValue = '{{ $key }}';
                                                setTimeout(() => { copied = false; copiedValue = ''; }, 2000);
                                            "
                                            class="w-full flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            :class="{ 'text-green-600': copied && copiedValue === '{{ $key }}' }"
                                        >
                                            <svg x-show="!(copied && copiedValue === '{{ $key }}')" class="w-4 h-4 mr-3 text-gray-400" fill="currentColor" viewBox="0 0 24 24">{!! $option['icon'] !!}</svg>
                                            <svg x-show="copied && copiedValue === '{{ $key }}'" x-cloak class="w-4 h-4 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span x-text="(copied && copiedValue === '{{ $key }}') ? '{{ __('Copied!') }}' : '{{ $option['label'] }}'"></span>
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                @if($isAdmin)
                    <a href="{{ route('manage.boards.show', $board) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-100 text-indigo-700 rounded-md text-sm font-medium hover:bg-indigo-200 transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{ __('Manage') }}
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="sm:px-6 lg:px-8 space-y-6">
            @if(session('success') || $autoClaimMessage)
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                    {{ session('success') ?? $autoClaimMessage }}
                </div>
            @endif

            @if(session('error') || $autoClaimError)
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    {{ session('error') ?? $autoClaimError }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                {{-- Main Grid Area --}}
                <div class="lg:col-span-3 space-y-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <x-board.grid
                                :board="$board"
                                :grid="$grid"
                                :user-squares="$userSquares"
                                :can-claim="$canClaim"
                                :is-guest="$isGuest"
                                :board-is-open="$boardIsOpen"
                                :is-admin="$isAdmin"
                                :winning-squares="$winningSquares"
                            />
                        </div>
                    </div>

                    {{-- Description --}}
                    @if($board->description)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('About This Board') }}</h3>
                                <p class="text-gray-600">{{ $board->description }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Sidebar --}}
                <div class="space-y-6">
                    {{-- Payment Instructions --}}
                    @if($board->payment_instructions)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-4">
                                <h3 class="text-sm font-semibold text-gray-900 mb-2">{{ __('Payment & Payout Info') }}</h3>
                                <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ $board->payment_instructions }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Payouts by Display Name - Show when there are winners --}}
                    @if($payoutsByDisplayName->isNotEmpty())
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-4">
                                <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ __('Winners') }}</h3>
                                <div class="space-y-2">
                                    @foreach($payoutsByDisplayName as $winnerData)
                                        <div class="flex justify-between items-center text-sm {{ $loop->odd ? 'bg-gray-50' : '' }} rounded px-2 py-1.5">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="text-gray-500 text-xs w-4">{{ $loop->iteration }}.</span>
                                                <span class="text-gray-900 truncate">{{ $winnerData['display_name'] }}</span>
                                            </div>
                                            <span class="font-medium text-green-600 whitespace-nowrap ml-2">${{ number_format($winnerData['total'] / 100, 2) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Admin Actions - Only show when board is full and open --}}
                    @if($isAdmin && $board->isOpen() && $board->isFull())
                        <div class="bg-violet-50 border border-violet-200 overflow-hidden sm:rounded-lg">
                            <div class="p-4">
                                <div class="flex items-center mb-3">
                                    <svg class="h-5 w-5 text-violet-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <h3 class="text-sm font-semibold text-violet-900">{{ __('Board Ready!') }}</h3>
                                </div>
                                <p class="text-sm text-violet-700 mb-3">All 100 squares have been claimed. Lock the board to reveal numbers and start the game.</p>
                                <form method="POST" action="{{ route('boards.lock', $board) }}" onsubmit="return confirm('Are you sure you want to lock this board? This will reveal numbers and prevent further square claims.');">
                                    @csrf
                                    <button type="submit" class="w-full px-4 py-2 bg-violet-600 text-white rounded-md text-sm font-medium hover:bg-violet-700 transition-colors">
                                        {{ __('Lock Board & Reveal Numbers') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                    {{-- Numbers Not Drawn Notice --}}
                    @if($isAdmin && $board->isLocked() && (empty($board->row_numbers) || empty($board->col_numbers)))
                        <div class="bg-blue-50 border border-blue-200 overflow-hidden sm:rounded-lg">
                            <div class="p-4">
                                <div class="flex items-center mb-3">
                                    <svg class="h-5 w-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <h3 class="text-sm font-semibold text-blue-900">{{ __('Numbers Not Drawn') }}</h3>
                                </div>
                                <p class="text-sm text-blue-700 mb-3">The board is locked but numbers still need to be assigned to rows and columns.</p>
                                <form method="POST" action="{{ route('manage.boards.generate-numbers', $board) }}" onsubmit="return confirm('Are you sure you want to draw numbers? This will randomly assign numbers 0-9 to rows and columns.');">
                                    @csrf
                                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 transition-colors">
                                        {{ __('Draw Numbers') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                    {{-- Board Stats --}}
                    <x-board.stats :board="$board" />

                    {{-- User's Squares --}}
                    @auth
                        @php
                            $mySquares = $board->squares->where('user_id', auth()->id());
                        @endphp
                        @if($mySquares->isNotEmpty())
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="p-4">
                                    <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ __('Your Squares') }}</h3>
                                    <div class="space-y-2">
                                        @foreach($mySquares as $square)
                                            <div class="flex justify-between items-center text-sm">
                                                <span class="text-gray-600">
                                                    @if($board->numbers_revealed)
                                                        {{ $board->team_row }} {{ $board->row_numbers[$square->row] ?? '?' }} - {{ $board->team_col }} {{ $board->col_numbers[$square->col] ?? '?' }}
                                                    @else
                                                        Row {{ $square->row + 1 }}, Col {{ $square->col + 1 }}
                                                    @endif
                                                </span>
                                                @if($square->is_paid)
                                                    <span class="text-green-600 text-xs">Paid</span>
                                                @else
                                                    <span class="text-yellow-600 text-xs">Unpaid</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endauth

                    {{-- Legend --}}
                    <x-board.legend />
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('squaresBoard', () => ({
                loading: false,
                canClaim: @json($canClaim),
                isGuest: @json($isGuest),
                boardIsOpen: @json($boardIsOpen),
                userSquares: @json($userSquares),

                async claimSquare(row, col) {
                    if (this.loading) return;

                    // Guests can attempt to claim - they'll be redirected to login
                    if (!this.canClaim && !this.isGuest) return;

                    this.loading = true;
                    try {
                        const response = await fetch(`/boards/{{ $board->uuid }}/squares/${row}/${col}/claim`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                        });

                        // If unauthorized, redirect to login with pending claim info
                        if (response.status === 401) {
                            const returnUrl = encodeURIComponent(window.location.href);
                            window.location.href = `/login?redirect=${returnUrl}&claim_board={{ $board->uuid }}&claim_row=${row}&claim_col=${col}`;
                            return;
                        }

                        const data = await response.json();

                        if (data.success) {
                            this.userSquares.push(data.square.id);
                            this.canClaim = data.can_claim_more;
                            location.reload(); // Refresh to update UI
                        } else {
                            alert(data.message);
                        }
                    } catch (error) {
                        alert('An error occurred. Please try again.');
                    } finally {
                        this.loading = false;
                    }
                },

                async releaseSquare(squareId) {
                    if (this.loading) return;

                    if (!confirm('Are you sure you want to release this square?')) return;

                    this.loading = true;
                    try {
                        const response = await fetch(`/boards/{{ $board->uuid }}/squares/${squareId}/release`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                        });

                        const data = await response.json();

                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.message);
                        }
                    } catch (error) {
                        alert('An error occurred. Please try again.');
                    } finally {
                        this.loading = false;
                    }
                },

                async markPaid(squareId) {
                    if (this.loading) return;

                    this.loading = true;
                    try {
                        const response = await fetch(`/manage/boards/{{ $board->uuid }}/squares/${squareId}/mark-paid`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                        });

                        if (response.ok) {
                            location.reload();
                        } else {
                            const data = await response.json();
                            alert(data.message || 'Failed to mark as paid.');
                        }
                    } catch (error) {
                        alert('An error occurred. Please try again.');
                    } finally {
                        this.loading = false;
                    }
                },

                async markUnpaid(squareId) {
                    if (this.loading) return;

                    this.loading = true;
                    try {
                        const response = await fetch(`/manage/boards/{{ $board->uuid }}/squares/${squareId}/mark-unpaid`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                        });

                        if (response.ok) {
                            location.reload();
                        } else {
                            const data = await response.json();
                            alert(data.message || 'Failed to mark as unpaid.');
                        }
                    } catch (error) {
                        alert('An error occurred. Please try again.');
                    } finally {
                        this.loading = false;
                    }
                }
            }));
        });
    </script>
    @endpush
</x-app-layout>
