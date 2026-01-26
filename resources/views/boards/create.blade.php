<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create New Board') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('boards.store') }}" class="space-y-6">
                        @csrf

                        {{-- Board Name --}}
                        <div>
                            <x-input-label for="name" :value="__('Board Name')" />
                            <x-text-input
                                id="name"
                                name="name"
                                type="text"
                                class="mt-1 block w-full"
                                :value="old('name')"
                                required
                                autofocus
                                placeholder="Super Bowl LVIII Pool"
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
                                    placeholder="superbowl-2025"
                                />
                            </div>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Letters, numbers, dashes, and underscores only. Leave blank to use the default URL.') }}</p>
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
                                placeholder="Annual family Super Bowl squares pool..."
                            >{{ old('description') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>

                        {{-- Teams --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="team_row" :value="__('Row Team (AFC/Home)')" />
                                <x-text-input
                                    id="team_row"
                                    name="team_row"
                                    type="text"
                                    class="mt-1 block w-full"
                                    :value="old('team_row')"
                                    required
                                    placeholder="Chiefs"
                                />
                                <x-input-error class="mt-2" :messages="$errors->get('team_row')" />
                            </div>

                            <div>
                                <x-input-label for="team_col" :value="__('Column Team (NFC/Away)')" />
                                <x-text-input
                                    id="team_col"
                                    name="team_col"
                                    type="text"
                                    class="mt-1 block w-full"
                                    :value="old('team_col')"
                                    required
                                    placeholder="Eagles"
                                />
                                <x-input-error class="mt-2" :messages="$errors->get('team_col')" />
                            </div>
                        </div>

                        {{-- Game Date --}}
                        <div>
                            <x-input-label for="game_date" :value="__('Game Date & Time (Optional)')" />
                            <x-text-input
                                id="game_date"
                                name="game_date"
                                type="datetime-local"
                                class="mt-1 block w-full"
                                :value="old('game_date')"
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('game_date')" />
                        </div>

                        {{-- Pricing --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="price_per_square" :value="__('Price Per Square ($)')" />
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
                                        :value="old('price_per_square', '10.00')"
                                        required
                                    />
                                </div>
                                <p class="mt-1 text-sm text-gray-500">{{ __('Total pot = 100 x price per square') }}</p>
                                <x-input-error class="mt-2" :messages="$errors->get('price_per_square')" />
                            </div>

                            <div>
                                <x-input-label for="max_squares_per_user" :value="__('Max Squares Per User')" />
                                <x-text-input
                                    id="max_squares_per_user"
                                    name="max_squares_per_user"
                                    type="number"
                                    min="1"
                                    max="100"
                                    class="mt-1 block w-full"
                                    :value="old('max_squares_per_user', '4')"
                                    required
                                />
                                <p class="mt-1 text-sm text-gray-500">{{ __('Limit how many squares each player can claim') }}</p>
                                <x-input-error class="mt-2" :messages="$errors->get('max_squares_per_user')" />
                            </div>
                        </div>

                        {{-- Visibility --}}
                        <div>
                            <div class="flex items-center">
                                <input
                                    id="is_public"
                                    name="is_public"
                                    type="checkbox"
                                    value="1"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    {{ old('is_public') ? 'checked' : '' }}
                                />
                                <x-input-label for="is_public" class="ml-2" :value="__('Make this board public')" />
                            </div>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Public boards can be found by anyone in the browse page.') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('is_public')" />
                        </div>

                        {{-- Payment & Payout Info --}}
                        <div>
                            <x-input-label for="payment_instructions" :value="__('Payment & Payout Info (Optional)')" />
                            <textarea
                                id="payment_instructions"
                                name="payment_instructions"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                placeholder="Venmo: @username&#10;Or cash at the party"
                            >{{ old('payment_instructions') }}</textarea>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Tell players how to pay for squares and how winners will be paid.') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('payment_instructions')" />
                        </div>

                        {{-- Submit --}}
                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200">
                            <a href="{{ route('boards.index') }}" class="text-gray-600 hover:text-gray-900">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button>
                                {{ __('Create Board') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
