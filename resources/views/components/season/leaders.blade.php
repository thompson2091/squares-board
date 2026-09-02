@props([
    'standings',
    'viewerId' => null,
    'limit' => 3,
    'standingsUrl' => null,
])

@php
    $leaders = $standings->take($limit);
    // Where the viewer sits, if they're not already in the top slice.
    $viewerRank = $viewerId === null
        ? null
        : $standings->search(fn (array $row): bool => $row['user']?->id === $viewerId);
    $showViewer = is_int($viewerRank) && $viewerRank >= $limit;
@endphp

@if($leaders->isNotEmpty())
    <div class="bg-white shadow-sm sm:rounded-lg">
        <div class="p-4">
            <div class="flex items-baseline justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-900">{{ __('Season Leaders') }}</h3>
                @if($standingsUrl)
                    <a href="{{ $standingsUrl }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">{{ __('All standings') }}</a>
                @endif
            </div>

            <div class="space-y-2">
                @foreach($leaders as $row)
                    @php $isYou = $viewerId !== null && $row['user']?->id === $viewerId; @endphp
                    <div class="flex justify-between items-center text-sm rounded px-2 py-1.5 {{ $isYou ? 'bg-indigo-50' : ($loop->odd ? 'bg-gray-50' : '') }}">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold flex-shrink-0 {{ ['bg-amber-100 text-amber-800', 'bg-gray-200 text-gray-700', 'bg-orange-100 text-orange-800'][$loop->index] ?? 'bg-gray-100 text-gray-500' }}">
                                {{ $loop->iteration }}
                            </span>
                            <span class="text-gray-900 truncate">{{ $row['display_name'] }}</span>
                            @if($isYou)
                                <span class="text-[10px] font-semibold uppercase tracking-wide text-indigo-600 flex-shrink-0">{{ __('You') }}</span>
                            @endif
                        </div>
                        <span class="font-medium text-green-600 whitespace-nowrap ml-2">${{ number_format($row['total'] / 100, 2) }}</span>
                    </div>
                @endforeach

                {{-- Keep the viewer visible even when they're well down the table --}}
                @if($showViewer)
                    @php $you = $standings[$viewerRank]; @endphp
                    <div class="pt-2 mt-2 border-t border-gray-100">
                        <div class="flex justify-between items-center text-sm rounded px-2 py-1.5 bg-indigo-50">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-100 text-gray-500 text-[10px] font-bold flex-shrink-0">
                                    {{ $viewerRank + 1 }}
                                </span>
                                <span class="text-gray-900 truncate">{{ $you['display_name'] }}</span>
                                <span class="text-[10px] font-semibold uppercase tracking-wide text-indigo-600 flex-shrink-0">{{ __('You') }}</span>
                            </div>
                            <span class="font-medium {{ $you['total'] > 0 ? 'text-green-600' : 'text-gray-400' }} whitespace-nowrap ml-2">
                                ${{ number_format($you['total'] / 100, 2) }}
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
