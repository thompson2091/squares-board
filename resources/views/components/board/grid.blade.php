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
                    <div class="aspect-square min-w-8 max-h-[4.75rem] flex items-center justify-center bg-gray-700 text-white rounded-t {{ $board->numbers_revealed && $board->col_numbers ? 'text-xl sm:text-2xl font-bold' : 'text-xs sm:text-sm font-semibold' }}">
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
                    <div class="aspect-square min-w-8 max-h-[4.75rem] flex items-center justify-center bg-gray-700 text-white {{ $row === 0 ? 'rounded-tl' : '' }} {{ $row === 9 ? 'rounded-bl' : '' }} {{ $board->numbers_revealed && $board->row_numbers ? 'text-xl sm:text-2xl font-bold' : 'text-xs sm:text-sm font-semibold' }}">
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

    {{-- Edit Name Modal --}}
    <div
        x-show="$store.board.activeModal === 'edit-name-modal'"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
    >
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$store.board.closeEditNameModal()"></div>

            <div
                x-show="$store.board.activeModal === 'edit-name-modal'"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative inline-block w-full max-w-md p-6 my-8 text-left align-middle bg-white rounded-lg shadow-xl transform transition-all"
                @click.stop
            >
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Edit Display Name') }}</h3>
                <p class="text-sm text-gray-500 mb-4">{{ __('Enter a custom name for this square, or leave blank to use your account name.') }}</p>

                <div class="mb-4">
                    <label for="edit-display-name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Display Name') }}</label>
                    <input
                        type="text"
                        id="edit-display-name"
                        x-model="$store.board.editingSquare.displayName"
                        @keydown.enter="$store.board.updateDisplayName($store.board.editingSquare.id, $store.board.editingSquare.displayName)"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        placeholder="{{ __('Your name') }}"
                    >
                </div>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        @click="$store.board.closeEditNameModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="$store.board.updateDisplayName($store.board.editingSquare.id, $store.board.editingSquare.displayName)"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        {{ __('Save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('board', {
            activeModal: null,
            editingSquare: null,
            loading: false,
            openEditNameModal(squareId, currentName) {
                this.editingSquare = { id: squareId, displayName: currentName || '' };
                this.activeModal = 'edit-name-modal';
                setTimeout(() => {
                    document.getElementById('edit-display-name')?.focus();
                }, 50);
            },
            closeEditNameModal() {
                this.editingSquare = null;
                this.activeModal = null;
            },
            async updateDisplayName(squareId, displayName) {
                if (this.loading) return;

                this.loading = true;
                try {
                    const response = await fetch(`/boards/{{ $board->uuid }}/squares/${squareId}/name`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ display_name: displayName || null }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.closeEditNameModal();
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to update display name.');
                    }
                } catch (error) {
                    alert('An error occurred. Please try again.');
                } finally {
                    this.loading = false;
                }
            }
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
