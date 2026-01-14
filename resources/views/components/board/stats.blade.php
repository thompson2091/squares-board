@props(['board'])

@php
    $fullPot = 100 * $board->price_per_square;
    $claimedCount = $board->claimedSquareCount();
    $paidCount = $board->paidSquareCount();
    $paymentPercent = $paidCount;

    // Group payout rules by quarter for display
    $payoutsByQuarter = $board->payoutRules->groupBy('quarter');
    $quarterConfig = [
        'Q1' => ['label' => 'Q1', 'bg' => 'bg-amber-50', 'border' => 'border-amber-200'],
        'Q2' => ['label' => 'Q2', 'bg' => 'bg-emerald-50', 'border' => 'border-emerald-200'],
        'Q3' => ['label' => 'Q3', 'bg' => 'bg-sky-50', 'border' => 'border-sky-200'],
        'final' => ['label' => 'Final', 'bg' => 'bg-violet-50', 'border' => 'border-violet-200'],
    ];

    // Winner type styling
    $winnerTypes = [
        'primary' => ['dot' => 'bg-green-500', 'label' => 'Winner'],
        'reverse' => ['dot' => 'bg-purple-500', 'label' => 'Reverse'],
        'touching' => ['dot' => 'bg-blue-500', 'label' => 'Touching'],
        '2mw' => ['dot' => 'bg-amber-500', 'label' => '2-Min Warning'],
    ];
@endphp

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-4 space-y-4">
        {{-- Pot Display --}}
        <div class="text-center py-3 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg">
            <div class="text-xs uppercase tracking-wide text-indigo-600 font-medium">{{ __('Pot') }}</div>
            <div class="text-3xl font-bold text-gray-900">${{ number_format($fullPot / 100, 2) }}</div>
            <div class="text-xs text-gray-500">{{ $board->price_display }} {{ __('per square') }}</div>
        </div>

        {{-- Payouts Section --}}
        @if($board->payoutRules->isNotEmpty())
            <div class="pt-3 border-t border-gray-200">
                <h4 class="text-sm font-semibold text-gray-900 mb-3">{{ __('Payouts') }}</h4>

                {{-- Quarter Cards Grid --}}
                <div class="grid grid-cols-2 gap-2">
                    @foreach(['Q1', 'Q2', 'Q3', 'final'] as $quarter)
                        @if(isset($payoutsByQuarter[$quarter]))
                            @php $config = $quarterConfig[$quarter]; @endphp
                            <div class="{{ $config['bg'] }} {{ $config['border'] }} border rounded-lg p-2">
                                <div class="text-xs font-semibold text-gray-700 mb-1">{{ $config['label'] }}</div>
                                <div class="space-y-1">
                                    @foreach($payoutsByQuarter[$quarter]->sortBy(fn($r) => array_search($r->winner_type, ['primary', 'reverse', 'touching', '2mw'])) as $rule)
                                        @php
                                            $payout = $rule->calculatePayout($fullPot);
                                            $type = $winnerTypes[$rule->winner_type] ?? $winnerTypes['primary'];
                                        @endphp
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full {{ $type['dot'] }} flex-shrink-0"></span>
                                            <span class="text-sm font-medium text-gray-900">${{ number_format($payout / 100, 0) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- Payout Legend --}}
                <div class="flex items-center justify-center gap-4 mt-3 text-xs text-gray-500">
                    @foreach($winnerTypes as $type => $config)
                        @if($board->payoutRules->contains('winner_type', $type))
                            <div class="flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full {{ $config['dot'] }}"></span>
                                <span>{{ $config['label'] }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @else
            <div class="pt-3 border-t border-gray-200">
                <p class="text-sm text-gray-500 text-center py-2">{{ __('No payouts configured') }}</p>
            </div>
        @endif

        {{-- Max Squares Info --}}
        <div class="text-center text-sm text-gray-600 pt-2 border-t border-gray-200">
            {{ __('Max :count squares per player', ['count' => $board->max_squares_per_user]) }}
        </div>

        {{-- Progress Bars --}}
        <div class="space-y-3 pt-2 border-t border-gray-200">
            {{-- Squares Claimed --}}
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600">{{ __('Squares Claimed') }}</span>
                    <span class="font-medium text-gray-900">{{ $claimedCount }}/100</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div
                        class="bg-indigo-600 h-2 rounded-full transition-all duration-300"
                        style="width: {{ $claimedCount }}%"
                    ></div>
                </div>
            </div>

            {{-- Payments Received --}}
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600">{{ __('Payments Received') }}</span>
                    <span class="font-medium text-gray-900">{{ $paidCount }}/100</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div
                        class="bg-green-600 h-2 rounded-full transition-all duration-300"
                        style="width: {{ $paymentPercent }}%"
                    ></div>
                </div>
            </div>
        </div>

        {{-- Owner Info --}}
        <div class="pt-2 border-t border-gray-200">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="text-sm text-gray-600">{{ __('Created by') }}</span>
                <span class="text-sm font-medium text-gray-900">{{ $board->owner->name }}</span>
            </div>
        </div>
    </div>
</div>
