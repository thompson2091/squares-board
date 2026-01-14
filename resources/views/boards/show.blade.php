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
                {{-- Copy Link Button --}}
                <button
                    type="button"
                    x-data="{ copied: false }"
                    @click="
                        navigator.clipboard.writeText('{{ url()->current() }}');
                        copied = true;
                        setTimeout(() => copied = false, 2000);
                    "
                    class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 transition-colors"
                    :class="{ 'bg-green-100 text-green-700': copied }"
                >
                    <svg x-show="!copied" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                    </svg>
                    <svg x-show="copied" x-cloak class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span x-text="copied ? '{{ __('Copied!') }}' : '{{ __('Copy Link') }}'"></span>
                </button>
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

                    {{-- Payouts by User - Show when there are winners --}}
                    @if($payoutsByUser->isNotEmpty())
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-4">
                                <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ __('Winners') }}</h3>
                                <div class="space-y-2">
                                    @foreach($payoutsByUser as $userData)
                                        <div class="flex justify-between items-center text-sm {{ $loop->odd ? 'bg-gray-50' : '' }} rounded px-2 py-1.5">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="text-gray-500 text-xs w-4">{{ $loop->iteration }}.</span>
                                                <span class="text-gray-900 truncate">{{ $userData['user']?->name ?? 'Unknown' }}</span>
                                            </div>
                                            <span class="font-medium text-green-600 whitespace-nowrap ml-2">${{ number_format($userData['total'] / 100, 2) }}</span>
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
