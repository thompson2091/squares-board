<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Season Settings') }}: {{ $season->name }}
            </h2>
            <a href="{{ route('manage.seasons.index', $season) }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                {{ __('Back to Manage') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('seasons.update', $season) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label for="name" :value="__('Season Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                :value="old('name', $season->name)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="slug" :value="__('Custom URL (Optional)')" />
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                                    {{ url('/') }}/
                                </span>
                                <x-text-input id="slug" name="slug" type="text"
                                    class="flex-1 block w-full rounded-none rounded-r-md"
                                    :value="old('slug', $season->slug)" />
                            </div>
                            <p class="mt-1 text-sm text-gray-500">{{ __('This link always shows the current week.') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('slug')" />
                        </div>

                        <div>
                            <x-input-label for="description" :value="__('Description (Optional)')" />
                            <textarea id="description" name="description" rows="3"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >{{ old('description', $season->description) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-200">
                            <div>
                                <x-input-label for="price_per_square" :value="__('Price Per Square, Per Week ($)')" />
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <x-text-input id="price_per_square" name="price_per_square" type="number"
                                        step="0.01" min="0.01" max="10000" class="block w-full pl-7"
                                        :value="old('price_per_square', number_format($season->price_per_square / 100, 2, '.', ''))" required />
                                </div>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ __(':total for the season across :n weeks.', ['total' => $season->season_total_display, 'n' => $season->total_weeks]) }}
                                </p>
                                <x-input-error class="mt-2" :messages="$errors->get('price_per_square')" />
                            </div>

                            <div>
                                <x-input-label for="max_squares_per_user" :value="__('Max Squares Per Player')" />
                                <x-text-input id="max_squares_per_user" name="max_squares_per_user" type="number"
                                    min="1" max="100" class="mt-1 block w-full"
                                    :value="old('max_squares_per_user', $season->max_squares_per_user)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('max_squares_per_user')" />
                            </div>
                        </div>

                        {{-- Week count is fixed once the boards exist --}}
                        <div>
                            <x-input-label :value="__('Number of Weeks')" />
                            <p class="mt-1 text-sm text-gray-600">
                                {{ $season->total_weeks }}
                                @if($season->hasStarted())
                                    <span class="text-gray-400">&mdash; {{ __('locked in; the weekly boards already exist') }}</span>
                                @endif
                            </p>
                        </div>

                        <div>
                            <x-input-label :value="__('When Are Numbers Drawn?')" />
                            <div class="mt-2 space-y-2">
                                @foreach([
                                    [\App\Models\Season::DRAW_UPFRONT, __('Draw every week up front'), __('Weeks get their numbers as soon as the season starts, hidden until each game.')],
                                    [\App\Models\Season::DRAW_MANUAL, __('Draw each week manually'), __('You run the draw yourself before each game.')],
                                ] as [$value, $label, $copy])
                                    <label class="flex items-start gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                                        <input type="radio" name="number_draw_mode" value="{{ $value }}"
                                            class="mt-0.5 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            {{ old('number_draw_mode', $season->number_draw_mode) === $value ? 'checked' : '' }} />
                                        <span class="min-w-0">
                                            <span class="block text-sm font-medium text-gray-900">{{ $label }}</span>
                                            <span class="block text-xs text-gray-500 mt-0.5">{{ $copy }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('number_draw_mode')" />
                        </div>

                        <div>
                            <div class="flex items-center">
                                <input id="is_public" name="is_public" type="checkbox" value="1"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    {{ old('is_public', $season->is_public) ? 'checked' : '' }} />
                                <x-input-label for="is_public" class="ml-2" :value="__('Make this season public')" />
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('is_public')" />
                        </div>

                        <div>
                            <x-input-label for="payment_instructions" :value="__('Payment & Payout Info (Optional)')" />
                            <input id="payment_instructions" type="hidden" name="payment_instructions"
                                value="{{ old('payment_instructions', $season->payment_instructions) }}" />
                            <trix-editor input="payment_instructions"
                                class="mt-1 trix-content prose prose-sm max-w-none border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></trix-editor>
                            <x-input-error class="mt-2" :messages="$errors->get('payment_instructions')" />
                        </div>

                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200">
                            <a href="{{ route('manage.seasons.index', $season) }}" class="text-gray-600 hover:text-gray-900">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button>{{ __('Save Season') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Deleting a season takes its roster and every weekly board with it --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-red-100">
                <div class="p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">{{ __('Delete this season') }}</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ __('Removes the roster and all :n weekly boards, along with their scores and winners.', ['n' => $season->total_weeks]) }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('seasons.destroy', $season) }}"
                          onsubmit="return confirm('Delete this season and every one of its boards? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>{{ __('Delete Season') }}</x-danger-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
