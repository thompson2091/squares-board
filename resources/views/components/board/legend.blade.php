@props([])

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-4">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ __('Legend') }}</h3>

        <div class="space-y-2">
            {{-- Available --}}
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 bg-gray-100 border border-gray-200 rounded flex items-center justify-center">
                    <span class="text-gray-400 text-sm">+</span>
                </div>
                <span class="text-sm text-gray-600">{{ __('Available') }}</span>
            </div>

            {{-- Your Square --}}
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 bg-orange-100 border border-gray-200 rounded flex items-center justify-center">
                    <span class="text-orange-700 text-xs font-medium">YO</span>
                </div>
                <span class="text-sm text-gray-600">{{ __('Your Square') }}</span>
            </div>

            {{-- Claimed Square --}}
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 bg-blue-100 border border-gray-200 rounded flex items-center justify-center">
                    <span class="text-blue-700 text-xs font-medium">AB</span>
                </div>
                <span class="text-sm text-gray-600">{{ __("Claimed Square") }}</span>
            </div>

            {{-- Paid --}}
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 bg-green-100 border border-gray-200 rounded flex items-center justify-center">
                    <span class="text-green-700 text-xs font-medium">AB</span>
                </div>
                <span class="text-sm text-gray-600">{{ __('Paid') }}</span>
            </div>
        </div>

        {{-- Winners Section --}}
        <div class="mt-4 pt-3 border-t border-gray-200">
            <h4 class="text-xs font-semibold text-gray-700 mb-2">{{ __('Winners') }}</h4>
            <div class="space-y-2">
                {{-- Primary Winner --}}
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-gray-100 border border-gray-200 rounded ring-4 ring-inset ring-violet-500"></div>
                    <span class="text-sm text-gray-600">{{ __('Primary') }}</span>
                </div>

                {{-- 2-Min Warning --}}
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-gray-100 border border-gray-200 rounded ring-[3px] ring-inset ring-amber-500"></div>
                    <span class="text-sm text-gray-600">{{ __('2-Min Warning') }}</span>
                </div>

                {{-- Reverse --}}
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-gray-100 border border-gray-200 rounded ring-2 ring-inset ring-cyan-500"></div>
                    <span class="text-sm text-gray-600">{{ __('Reverse') }}</span>
                </div>

                {{-- Touching --}}
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-gray-100 border border-gray-200 rounded ring-2 ring-inset ring-pink-500"></div>
                    <span class="text-sm text-gray-600">{{ __('Touching') }}</span>
                </div>
            </div>
        </div>

        <div class="mt-4 pt-3 border-t border-gray-200">
            <p class="text-xs text-gray-500">
                {{ __('Click an available square to claim it. Click your square to release it.') }}
            </p>
        </div>
    </div>
</div>
