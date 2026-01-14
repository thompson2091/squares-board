@props(['board', 'grid', 'userSquares' => [], 'canClaim' => false, 'isGuest' => false, 'boardIsOpen' => false, 'isAdmin' => false, 'winningSquares' => []])

@php
    // Guests can attempt to claim squares if board is open (they'll be redirected to login)
    $canAttemptClaim = $canClaim || ($isGuest && $boardIsOpen);
@endphp

<div x-data="squaresBoard()" @click.away="$store.board.activeModal = null" class="overflow-x-auto">
    {{-- Column Team Header --}}
    <div class="text-center mb-2">
        <span class="text-lg font-bold text-gray-900">{{ $board->team_col }}</span>
    </div>

    <div class="flex">
        {{-- Row Team Label (rotated) --}}
        <div class="flex items-center justify-center w-8 mr-2">
            <span class="text-lg font-bold text-gray-900 transform -rotate-90 whitespace-nowrap">{{ $board->team_row }}</span>
        </div>

        <div class="flex-1">
            {{-- Column Numbers Header --}}
            <div class="grid grid-cols-11 gap-px mb-px">
                <div class="aspect-square min-w-8 max-h-[4.75rem]"></div> {{-- Empty corner --}}
                @for($col = 0; $col < 10; $col++)
                    <div class="aspect-square min-w-8 max-h-[4.75rem] flex items-center justify-center bg-gray-700 text-white text-xs sm:text-sm font-semibold rounded-t">
                        @if($board->numbers_revealed && $board->col_numbers)
                            {{ $board->col_numbers[$col] }}
                        @else
                            ?
                        @endif
                    </div>
                @endfor
            </div>

            {{-- Grid Rows --}}
            @for($row = 0; $row < 10; $row++)
                <div class="grid grid-cols-11 gap-px mb-px">
                    {{-- Row Number --}}
                    <div class="aspect-square min-w-8 max-h-[4.75rem] flex items-center justify-center bg-gray-700 text-white text-xs sm:text-sm font-semibold {{ $row === 0 ? 'rounded-tl' : '' }} {{ $row === 9 ? 'rounded-bl' : '' }}">
                        @if($board->numbers_revealed && $board->row_numbers)
                            {{ $board->row_numbers[$row] }}
                        @else
                            ?
                        @endif
                    </div>

                    {{-- Squares --}}
                    @for($col = 0; $col < 10; $col++)
                        @php
                            $square = $grid[$row][$col] ?? null;
                            $isClaimed = $square && $square->isClaimed();
                            $isOwn = $square && in_array($square->id, $userSquares);
                            $isPaid = $square && $square->is_paid;
                            $winnerTypes = $square ? ($winningSquares[$square->id] ?? []) : [];
                        @endphp

                        <x-board.square
                            :square="$square"
                            :row="$row"
                            :col="$col"
                            :is-claimed="$isClaimed"
                            :is-own="$isOwn"
                            :is-paid="$isPaid"
                            :can-claim="$canAttemptClaim && !$isClaimed && $board->isOpen()"
                            :is-admin="$isAdmin"
                            :board-status="$board->status"
                            :numbers-revealed="$board->numbers_revealed"
                            :row-number="$board->row_numbers[$row] ?? null"
                            :col-number="$board->col_numbers[$col] ?? null"
                            :winner-types="$winnerTypes"
                            :team-row="$board->team_row"
                            :team-col="$board->team_col"
                        />
                    @endfor
                </div>
            @endfor
        </div>
    </div>

    {{-- Mobile Instructions --}}
    <div class="mt-4 text-center text-sm text-gray-500 sm:hidden">
        {{ __('Scroll horizontally to see the full grid') }}
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('board', {
            activeModal: null
        });
    });

    function squaresBoard() {
        return {
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
        };
    }
</script>
