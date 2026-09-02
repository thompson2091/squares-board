<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $season->name }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ __('Season pool') }}
                    <span class="mx-2">|</span>
                    {{ __(':n weeks', ['n' => $season->total_weeks]) }}
                </p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $season->status_color }}">
                {{ $season->status_display }}
            </span>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="sm:px-6 lg:px-8 space-y-4">
            <x-season.bar :season="$season" :is-standings="true" :my-winnings="$myWinnings" />

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <div class="lg:col-span-3 space-y-4">
                    {{-- Season totals --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach([
                            [__('Paid Out'), '$'.number_format($paidOut / 100, 2), __('across :n weeks', ['n' => $weeksPlayed])],
                            [__('Weeks Played'), $weeksPlayed.' / '.$season->total_weeks, __('remaining: :n', ['n' => $season->total_weeks - $weeksPlayed])],
                            [__('Weekly Pot'), '$'.number_format($weeklyPot / 100, 2), __(':n squares x :price', ['n' => $claimedCount, 'price' => $season->price_display])],
                            [__('Players'), (string) $standings->count(), __('holding :n squares', ['n' => $claimedCount])],
                        ] as [$label, $value, $sub])
                            <div class="bg-white shadow-sm sm:rounded-lg px-4 py-3">
                                <div class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">{{ $label }}</div>
                                <div class="text-xl font-bold text-gray-900 leading-tight mt-0.5">{{ $value }}</div>
                                <div class="text-[11px] text-gray-500 mt-0.5">{{ $sub }}</div>
                            </div>
                        @endforeach
                    </div>

                    {{-- The table --}}
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200">
                            <h3 class="text-base font-semibold text-gray-900">{{ __('Standings') }}</h3>
                            <p class="text-sm text-gray-500 mt-0.5">
                                {{ __('Every week added together. Players with no wins yet are still listed.') }}
                            </p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        @foreach([__('#'), __('Player'), __('Squares'), __('Weeks Won'), __('Wins'), __('Total Won')] as $index => $heading)
                                            <th class="px-4 py-2 text-[10px] font-semibold uppercase tracking-wide text-gray-500 whitespace-nowrap {{ $index >= 2 ? 'text-right' : 'text-left' }}">
                                                {{ $heading }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($standings as $row)
                                        @php $isYou = $viewerId !== null && $row['user']?->id === $viewerId; @endphp
                                        <tr class="{{ $isYou ? 'bg-indigo-50/60' : '' }}">
                                            <td class="px-4 py-2 whitespace-nowrap">
                                                @if($loop->iteration <= 3 && $row['total'] > 0)
                                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold {{ ['bg-amber-100 text-amber-800', 'bg-gray-200 text-gray-700', 'bg-orange-100 text-orange-800'][$loop->index] }}">
                                                        {{ $loop->iteration }}
                                                    </span>
                                                @else
                                                    <span class="text-sm text-gray-400 pl-2">{{ $loop->iteration }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="text-sm font-medium text-gray-900 truncate">{{ $row['display_name'] }}</span>
                                                    @if($isYou)
                                                        <span class="px-1.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700 text-[10px] font-semibold uppercase tracking-wide">{{ __('You') }}</span>
                                                    @endif
                                                    @if($row['paid_squares'] < $row['squares'])
                                                        <span class="px-1.5 py-0.5 rounded-full bg-yellow-100 text-yellow-800 text-[10px] font-semibold uppercase tracking-wide" title="{{ __(':n of :total squares paid', ['n' => $row['paid_squares'], 'total' => $row['squares']]) }}">
                                                            {{ __('Owes') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-2 text-right text-sm text-gray-600 tabular-nums">{{ $row['squares'] }}</td>
                                            <td class="px-4 py-2 text-right text-sm text-gray-600 tabular-nums">{{ $row['weeks_won'] ?: '-' }}</td>
                                            <td class="px-4 py-2 text-right text-sm text-gray-600 tabular-nums">{{ $row['wins'] ?: '-' }}</td>
                                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                                @if($row['total'] > 0)
                                                    <span class="text-sm font-semibold text-green-600 tabular-nums">${{ number_format($row['total'] / 100, 2) }}</span>
                                                @else
                                                    <span class="text-sm text-gray-300">&mdash;</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Sidebar --}}
                <div class="space-y-4">
                    <x-season.leaders :standings="$standings" :viewer-id="$viewerId" />

                    <div class="bg-white shadow-sm sm:rounded-lg">
                        <div class="p-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ __('Week by Week') }}</h3>
                            <div class="space-y-1.5">
                                @foreach($season->weeks as $week)
                                    @if($week->isCompleted() || $week->isCurrent())
                                        <a href="{{ $season->weekUrl($week->number) }}" class="flex items-center justify-between text-sm rounded px-2 py-1 hover:bg-gray-50">
                                            <span class="text-gray-600 truncate">
                                                <span class="text-gray-400 text-xs mr-1">{{ __('Wk :n', ['n' => $week->number]) }}</span>
                                                {{ $week->matchup }}
                                            </span>
                                            <span class="text-xs {{ $week->isCurrent() ? 'text-green-600' : 'text-gray-400' }} whitespace-nowrap ml-2">
                                                {{ $week->isCurrent() ? __('Live') : __('Final') }}
                                            </span>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
