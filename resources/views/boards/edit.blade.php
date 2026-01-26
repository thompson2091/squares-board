<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('boards.show', $board) }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Board Settings') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Board Settings Form --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('boards.update', $board) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        {{-- Board Name --}}
                        <div>
                            <x-input-label for="name" :value="__('Board Name')" />
                            <x-text-input
                                id="name"
                                name="name"
                                type="text"
                                class="mt-1 block w-full"
                                :value="old('name', $board->name)"
                                required
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
                                    :value="old('slug', $board->slug)"
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
                            >{{ old('description', $board->description) }}</textarea>
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
                                    :value="old('team_row', $board->team_row)"
                                    required
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
                                    :value="old('team_col', $board->team_col)"
                                    required
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
                                :value="old('game_date', $board->game_date?->format('Y-m-d\TH:i'))"
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
                                        :value="old('price_per_square', number_format($board->price_per_square / 100, 2, '.', ''))"
                                        required
                                    />
                                </div>
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
                                    :value="old('max_squares_per_user', $board->max_squares_per_user)"
                                    required
                                />
                                <x-input-error class="mt-2" :messages="$errors->get('max_squares_per_user')" />
                            </div>
                        </div>

                        {{-- Status --}}
                        <div>
                            <x-input-label for="status" :value="__('Board Status')" />
                            <select
                                id="status"
                                name="status"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <option value="draft" {{ old('status', $board->status) === 'draft' ? 'selected' : '' }}>
                                    {{ __('Draft - Not visible, cannot claim') }}
                                </option>
                                <option value="open" {{ old('status', $board->status) === 'open' ? 'selected' : '' }}>
                                    {{ __('Open - Players can claim squares') }}
                                </option>
                                <option value="locked" {{ old('status', $board->status) === 'locked' ? 'selected' : '' }}>
                                    {{ __('Locked - No more claims allowed') }}
                                </option>
                                <option value="completed" {{ old('status', $board->status) === 'completed' ? 'selected' : '' }}>
                                    {{ __('Completed - Game finished') }}
                                </option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('status')" />
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
                                    {{ old('is_public', $board->is_public) ? 'checked' : '' }}
                                />
                                <x-input-label for="is_public" class="ml-2" :value="__('Make this board public')" />
                            </div>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Public boards can be found by anyone.') }}</p>
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
                            >{{ old('payment_instructions', $board->payment_instructions) }}</textarea>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Tell players how to pay for squares and how winners will be paid.') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('payment_instructions')" />
                        </div>

                        {{-- Submit --}}
                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200">
                            <a href="{{ route('boards.show', $board) }}" class="text-gray-600 hover:text-gray-900">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button>
                                {{ __('Save Changes') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Danger Zone --}}
            @if($board->owner_id === auth()->id())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-red-200">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-red-600 mb-4">{{ __('Danger Zone') }}</h3>

                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-gray-900">{{ __('Delete This Board') }}</h4>
                                <p class="text-sm text-gray-500">{{ __('Once deleted, all data will be permanently removed.') }}</p>
                            </div>
                            <form method="POST" action="{{ route('boards.destroy', $board) }}" onsubmit="return confirm('Are you sure you want to delete this board? This action cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 transition-colors">
                                    {{ __('Delete Board') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
