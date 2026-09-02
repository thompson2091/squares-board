<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Season Pool') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-6 pb-6 border-b border-gray-200">
                        {{ __('A season pool runs one grid across many games. Players claim a square once and keep it every week, and fresh numbers are drawn for each game.') }}
                    </p>

                    <form
                        method="POST"
                        action="{{ route('seasons.store') }}"
                        class="space-y-6"
                        x-data="{
                            price: {{ old('price_per_square', '5.00') }},
                            weeks: {{ old('total_weeks', 18) }},
                            get seasonTotal() {
                                return (Number(this.price || 0) * Number(this.weeks || 0)).toFixed(2);
                            },
                        }"
                    >
                        @csrf

                        {{-- Season Name --}}
                        <div>
                            <x-input-label for="name" :value="__('Season Name')" />
                            <x-text-input
                                id="name"
                                name="name"
                                type="text"
                                class="mt-1 block w-full"
                                :value="old('name')"
                                required
                                autofocus
                                placeholder="Sunday Night Squares 2026"
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        {{-- URL Slug --}}
                        <div>
                            <x-input-label for="slug" :value="__('Custom URL (Optional)')" />
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                                    {{ url('/') }}/
                                </span>
                                <x-text-input
                                    id="slug"
                                    name="slug"
                                    type="text"
                                    class="flex-1 block w-full rounded-none rounded-r-md"
                                    :value="old('slug')"
                                    placeholder="sunday-night-squares"
                                />
                            </div>
                            <p class="mt-1 text-sm text-gray-500">{{ __('This one link is all players ever need - it always shows the current week.') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('slug')" />
                        </div>

                        {{-- Description --}}
                        <div>
                            <x-input-label for="description" :value="__('Description (Optional)')" />
                            <textarea
                                id="description"
                                name="description"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                placeholder="One square, every week of the season..."
                            >{{ old('description') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>

                        {{-- Season setup --}}
                        <div class="pt-6 border-t border-gray-200 space-y-6">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">{{ __('Season Setup') }}</h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ __("Matchups aren't set here - you fill in each week's teams as the schedule firms up.") }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- Weeks --}}
                                <div>
                                    <x-input-label for="total_weeks" :value="__('Number of Weeks')" />
                                    <x-text-input
                                        id="total_weeks"
                                        name="total_weeks"
                                        type="number"
                                        min="2"
                                        max="30"
                                        class="mt-1 block w-full"
                                        x-model="weeks"
                                        :value="old('total_weeks', 18)"
                                        required
                                    />
                                    <p class="mt-1 text-sm text-gray-500">{{ __('One board is created per week.') }}</p>
                                    <x-input-error class="mt-2" :messages="$errors->get('total_weeks')" />
                                </div>

                                {{-- Price --}}
                                <div>
                                    <x-input-label for="price_per_square" :value="__('Price Per Square, Per Week ($)')" />
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">$</span>
                                        </div>
                                        <x-text-input
                                            id="price_per_square"
                                            name="price_per_square"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            max="10000"
                                            class="block w-full pl-7"
                                            x-model="price"
                                            :value="old('price_per_square', '5.00')"
                                            required
                                        />
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500">{{ __('Weekly pot = 100 x this price.') }}</p>
                                    <x-input-error class="mt-2" :messages="$errors->get('price_per_square')" />
                                </div>
                            </div>

                            {{-- What a square actually costs --}}
                            <div class="bg-indigo-50 border border-indigo-100 rounded-lg px-4 py-3">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="text-sm text-indigo-900">
                                        <span class="font-semibold" x-text="'$' + Number(price || 0).toFixed(2)"></span>
                                        {{ __('per week') }}
                                        <span class="text-indigo-400 mx-1">&times;</span>
                                        <span class="font-semibold" x-text="weeks"></span>
                                        {{ __('weeks') }}
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xl font-bold text-indigo-900" x-text="'$' + seasonTotal"></div>
                                        <div class="text-[11px] uppercase tracking-wide text-indigo-500">{{ __('per square, up front') }}</div>
                                    </div>
                                </div>
                                <p class="text-xs text-indigo-600 mt-2">
                                    {{ __('Players pay once for the whole season. Every week inherits that payment.') }}
                                </p>
                            </div>

                            {{-- Number draw --}}
                            <div>
                                <x-input-label :value="__('When Are Numbers Drawn?')" />
                                <div class="mt-2 space-y-2">
                                    @foreach([
                                        ['upfront', __('Draw every week up front'), __('All weeks get their numbers the moment the season starts. Each week stays hidden until its game.')],
                                        ['manual', __('Draw each week manually'), __('You run the draw yourself before each game - better if you like doing it live.')],
                                    ] as [$value, $label, $copy])
                                        <label class="flex items-start gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                                            <input
                                                type="radio"
                                                name="number_draw_mode"
                                                value="{{ $value }}"
                                                class="mt-0.5 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                {{ old('number_draw_mode', 'upfront') === $value ? 'checked' : '' }}
                                            />
                                            <span class="min-w-0">
                                                <span class="block text-sm font-medium text-gray-900">{{ $label }}</span>
                                                <span class="block text-xs text-gray-500 mt-0.5">{{ $copy }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <x-input-error class="mt-2" :messages="$errors->get('number_draw_mode')" />
                            </div>

                            {{-- Max squares --}}
                            <div>
                                <x-input-label for="max_squares_per_user" :value="__('Max Squares Per Player')" />
                                <x-text-input
                                    id="max_squares_per_user"
                                    name="max_squares_per_user"
                                    type="number"
                                    min="1"
                                    max="100"
                                    class="mt-1 block w-full md:w-1/2"
                                    :value="old('max_squares_per_user', '10')"
                                    required
                                />
                                <p class="mt-1 text-sm text-gray-500">{{ __('Applies to the season roster, not to each week.') }}</p>
                                <x-input-error class="mt-2" :messages="$errors->get('max_squares_per_user')" />
                            </div>
                        </div>

                        {{-- Visibility --}}
                        <div class="pt-6 border-t border-gray-200">
                            <div class="flex items-center">
                                <input
                                    id="is_public"
                                    name="is_public"
                                    type="checkbox"
                                    value="1"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    {{ old('is_public') ? 'checked' : '' }}
                                />
                                <x-input-label for="is_public" class="ml-2" :value="__('Make this season public')" />
                            </div>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Public seasons can be found by anyone in the browse page.') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('is_public')" />
                        </div>

                        {{-- Payment & Payout Info --}}
                        <div>
                            <x-input-label for="payment_instructions" :value="__('Payment & Payout Info (Optional)')" />
                            <input
                                id="payment_instructions"
                                type="hidden"
                                name="payment_instructions"
                                value="{{ old('payment_instructions') }}"
                            />
                            <trix-editor
                                input="payment_instructions"
                                class="mt-1 trix-content prose prose-sm max-w-none border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Venmo: @username for the full season..."
                            ></trix-editor>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Tell players how to pay for the season and how weekly winners are paid.') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('payment_instructions')" />
                        </div>

                        {{-- Submit --}}
                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200">
                            <a href="{{ route('boards.index') }}" class="text-gray-600 hover:text-gray-900">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button>
                                {{ __('Create Season') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
