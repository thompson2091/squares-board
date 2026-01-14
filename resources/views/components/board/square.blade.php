@props([
    'square' => null,
    'row',
    'col',
    'isClaimed' => false,
    'isOwn' => false,
    'isPaid' => false,
    'canClaim' => false,
    'isAdmin' => false,
    'isGuest' => false,
    'boardStatus' => 'draft',
    'numbersRevealed' => false,
    'rowNumber' => null,
    'colNumber' => null,
    'winnerTypes' => [],
    'teamRow' => '',
    'teamCol' => '',
])

@php
    // Determine background color
    if (!$isClaimed) {
        $bgColor = $canClaim ? 'bg-gray-100 hover:bg-gray-200 cursor-pointer' : 'bg-gray-100';
    } elseif ($isPaid) {
        $bgColor = 'bg-green-100';
    } elseif ($isOwn) {
        $bgColor = 'bg-orange-100';
    } else {
        $bgColor = 'bg-blue-100';
    }

    // Admin can interact with claimed squares
    $adminCanManage = $isAdmin && $isClaimed;

    // Can user release this square?
    $canRelease = $isOwn && !$isPaid && in_array($boardStatus, ['draft', 'open']);

    // Show details popup for claimed squares the user can't release/manage
    $showDetailsOnClick = $isClaimed && !$adminCanManage && !$canRelease;

    // Position dropdown to prevent overflow scroll
    // Vertical: last 3 rows show above, others below
    $isBottomRows = $row >= 7;
    $verticalPosition = $isBottomRows ? 'bottom-full mb-1' : 'top-full mt-1';

    // Horizontal: first 3 cols show to right, last 3 cols show to left, middle centered
    $isLeftCols = $col <= 2;
    $isRightCols = $col >= 7;
    if ($isLeftCols) {
        $horizontalPosition = 'left-0';
    } elseif ($isRightCols) {
        $horizontalPosition = 'right-0';
    } else {
        $horizontalPosition = 'left-1/2 -translate-x-1/2';
    }

    $dropdownPosition = $verticalPosition . ' ' . $horizontalPosition;

    // Winner styling - prioritize primary as most prominent
    // winnerTypes is now array of objects: [{type: 'primary', quarter: 'Q1'}, ...]
    $isWinner = !empty($winnerTypes);
    $winnerRingClass = '';
    if ($isWinner) {
        $types = array_column($winnerTypes, 'type');
        if (in_array('primary', $types)) {
            $winnerRingClass = 'ring-4 ring-inset ring-violet-500';
        } elseif (in_array('2mw', $types)) {
            $winnerRingClass = 'ring-[3px] ring-inset ring-amber-500';
        } elseif (in_array('reverse', $types)) {
            $winnerRingClass = 'ring-2 ring-inset ring-cyan-500';
        } elseif (in_array('touching', $types)) {
            $winnerRingClass = 'ring-2 ring-inset ring-pink-500';
        }
    }

    // Helper to format quarter label
    $formatQuarter = fn($q) => $q === 'final' ? 'Final' : $q;
@endphp

<div
    @if($canClaim && !$isClaimed)
        @click="claimSquare({{ $row }}, {{ $col }})"
        role="button"
        tabindex="0"
        @keydown.enter="claimSquare({{ $row }}, {{ $col }})"
        aria-label="Claim square at row {{ $row }}, column {{ $col }}"
    @elseif($canRelease)
        @click="releaseSquare({{ $square?->id }})"
        role="button"
        tabindex="0"
        @keydown.enter="releaseSquare({{ $square?->id }})"
        aria-label="Release your square at row {{ $row }}, column {{ $col }}"
    @elseif($adminCanManage)
        @click.stop="$store.board.activeModal = $store.board.activeModal === 'menu-{{ $row }}-{{ $col }}' ? null : 'menu-{{ $row }}-{{ $col }}'"
        role="button"
        tabindex="0"
        aria-label="Manage square at row {{ $row }}, column {{ $col }}"
    @elseif($showDetailsOnClick)
        @click.stop="$store.board.activeModal = $store.board.activeModal === 'details-{{ $row }}-{{ $col }}' ? null : 'details-{{ $row }}-{{ $col }}'"
        role="button"
        tabindex="0"
        aria-label="View square details"
    @endif
    class="aspect-square min-w-8 max-h-[4.75rem] flex items-center justify-center text-xs sm:text-sm {{ $bgColor }} border border-gray-200 relative transition-colors duration-150 select-none {{ $adminCanManage || $showDetailsOnClick ? 'cursor-pointer' : '' }} {{ $adminCanManage && !$isWinner ? 'ring-inset hover:ring-2 hover:ring-indigo-300' : '' }} {{ $showDetailsOnClick && !$isWinner ? 'hover:ring-1 hover:ring-gray-300' : '' }} {{ $winnerRingClass }}"
    @php
        if ($numbersRevealed && $rowNumber !== null && $colNumber !== null) {
            $positionInfo = $rowNumber . '-' . $colNumber;
        } else {
            $positionInfo = 'Row ' . ($row + 1) . ', Col ' . ($col + 1) . ($isClaimed ? ($isPaid ? ' (Paid)' : ' (Unpaid)') : '');
        }
        $titleText = $isClaimed
            ? ($square?->user?->name ?? 'Claimed') . ' - ' . $positionInfo
            : 'Available - ' . $positionInfo;
    @endphp
    title="{{ $titleText }}"
>
    {{-- Content --}}
    @if($isClaimed)
        {{-- Show name for all claimed squares --}}
        @php
            if ($isPaid) {
                $textColor = 'text-green-700';
            } elseif ($isOwn) {
                $textColor = 'text-orange-700';
            } else {
                $textColor = 'text-blue-700';
            }
        @endphp
        <span class="font-medium {{ $textColor }} truncate px-0.5 leading-tight text-center">
            @if($square?->user)
                {{ \Illuminate\Support\Str::limit($square->user->name, 8, '') }}
            @else
                {{ __('--') }}
            @endif
        </span>
    @else
        {{-- Empty unclaimed square --}}
        @if($canClaim)
            <span class="text-gray-400 text-lg">+</span>
        @endif
    @endif

    {{-- Details popup for guests/viewers --}}
    @if($showDetailsOnClick)
        <div
            x-show="$store.board.activeModal === 'details-{{ $row }}-{{ $col }}'"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 {{ $dropdownPosition }} min-w-[220px] bg-white rounded-lg shadow-xl border border-gray-200 py-2 px-3 text-left whitespace-nowrap"
            @click.stop
        >
            <div class="flex items-center justify-between gap-2">
                <span class="text-sm font-medium text-gray-900">{{ $square?->user?->name ?? 'Unknown' }}</span>
            </div>
            @if($isWinner)
                <div class="flex flex-wrap gap-1 mt-1.5">
                    @foreach($winnerTypes as $winData)
                        @php $quarter = $formatQuarter($winData['quarter']); @endphp
                        @if($winData['type'] === 'primary')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ $quarter }} Primary</span>
                        @elseif($winData['type'] === '2mw')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">{{ $quarter }} 2MW</span>
                        @elseif($winData['type'] === 'reverse')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">{{ $quarter }} Reverse</span>
                        @elseif($winData['type'] === 'touching')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $quarter }} Touching</span>
                        @endif
                    @endforeach
                </div>
            @endif
            <div class="text-xs text-gray-500 mt-1">
                @if($numbersRevealed && $rowNumber !== null && $colNumber !== null)
                    {{ $teamRow }} {{ $rowNumber }} - {{ $teamCol }} {{ $colNumber }}
                @else
                    Row {{ $row + 1 }}, Col {{ $col + 1 }}
                    <span class="mx-1">·</span>
                    @if($isPaid)
                        <span class="text-green-600">Paid</span>
                    @else
                        <span class="text-yellow-600">Unpaid</span>
                    @endif
                @endif
            </div>
        </div>
    @endif

    {{-- Admin dropdown menu --}}
    @if($adminCanManage)
        <div
            x-show="$store.board.activeModal === 'menu-{{ $row }}-{{ $col }}'"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 {{ $dropdownPosition }} min-w-[220px] bg-white rounded-lg shadow-xl border border-gray-200 py-1 text-left"
            @click.stop
        >
            <div class="px-4 py-2 border-b border-gray-100">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-medium text-gray-700">{{ $square?->user?->name ?? 'Unknown' }}</span>
                </div>
                @if($isWinner)
                    <div class="flex flex-wrap gap-1 mt-1">
                        @foreach($winnerTypes as $winData)
                            @php $quarter = $formatQuarter($winData['quarter']); @endphp
                            @if($winData['type'] === 'primary')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ $quarter }} Primary</span>
                            @elseif($winData['type'] === '2mw')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">{{ $quarter }} 2MW</span>
                            @elseif($winData['type'] === 'reverse')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">{{ $quarter }} Reverse</span>
                            @elseif($winData['type'] === 'touching')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $quarter }} Touching</span>
                            @endif
                        @endforeach
                    </div>
                @endif
                <div class="text-xs text-gray-400 mt-1">
                    @if($numbersRevealed && $rowNumber !== null && $colNumber !== null)
                        {{ $teamRow }} {{ $rowNumber }} - {{ $teamCol }} {{ $colNumber }}
                    @else
                        Row {{ $row + 1 }}, Col {{ $col + 1 }}
                        <span class="mx-1">·</span>
                        @if($isPaid)
                            <span class="text-green-600">Paid</span>
                        @else
                            <span class="text-yellow-600">Unpaid</span>
                        @endif
                    @endif
                </div>
            </div>
            @if(!$isPaid)
                <button
                    @click="markPaid({{ $square?->id }}); $store.board.activeModal = null"
                    class="w-full px-4 py-3 text-sm text-left text-gray-700 bg-white hover:bg-green-100 flex items-center gap-3 cursor-pointer"
                >
                    <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Mark as Paid</span>
                </button>
            @else
                <button
                    @click="markUnpaid({{ $square?->id }}); $store.board.activeModal = null"
                    class="w-full px-4 py-3 text-sm text-left text-gray-700 bg-white hover:bg-yellow-100 flex items-center gap-3 cursor-pointer"
                >
                    <svg class="w-4 h-4 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>Unmark as Paid</span>
                </button>
            @endif
            <button
                @click="$store.board.activeModal = null; if(confirm('Release this square? {{ $square?->user?->name ?? 'The user' }} will lose their claim.')) { releaseSquare({{ $square?->id }}); }"
                class="w-full px-4 py-3 text-sm text-left text-gray-700 bg-white hover:bg-red-100 flex items-center gap-3 cursor-pointer"
            >
                <svg class="w-4 h-4 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span>Release Square</span>
            </button>
        </div>
    @endif
</div>
