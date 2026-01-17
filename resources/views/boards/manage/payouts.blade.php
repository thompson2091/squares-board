<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Payout Rules: {{ $board->name }}
            </h2>
            <a href="{{ route('manage.boards.show', $board) }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                Back to Management
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Payout Summary Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Payout Summary</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-500">Potential Pot</div>
                            <div class="text-2xl font-bold text-gray-900">${{ number_format($potentialPot / 100, 2) }}</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-500">Percentage Allocated</div>
                            <div class="text-2xl font-bold {{ $totalPercentage > 10000 ? 'text-red-600' : ($totalPercentage == 10000 ? 'text-green-600' : 'text-gray-900') }}">
                                {{ number_format($totalPercentage / 100, 2) }}%
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-500">Fixed Payouts</div>
                            <div class="text-2xl font-bold text-gray-900">${{ number_format($totalFixedCents / 100, 2) }}</div>
                        </div>
                        @php
                            $totalPayoutCents = ($potentialPot * $totalPercentage / 10000) + $totalFixedCents;
                            $remaining = $potentialPot - $totalPayoutCents;
                        @endphp
                        <div class="bg-{{ $remaining < 0 ? 'red' : ($remaining == 0 ? 'green' : 'yellow') }}-50 rounded-lg p-4">
                            <div class="text-sm text-{{ $remaining < 0 ? 'red' : ($remaining == 0 ? 'green' : 'yellow') }}-600">
                                {{ $remaining < 0 ? 'Over Budget' : ($remaining == 0 ? 'Fully Allocated' : 'Remaining') }}
                            </div>
                            <div class="text-2xl font-bold text-{{ $remaining < 0 ? 'red' : ($remaining == 0 ? 'green' : 'yellow') }}-600">
                                ${{ number_format(abs($remaining) / 100, 2) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payout Rule Types -->
            <div class="bg-blue-50 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-blue-900 mb-2">Payout Rule Types</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                        <div>
                            <dt class="font-medium text-blue-800">Primary Winner</dt>
                            <dd class="text-blue-700">The square matching both team score digits (last digit of each score).</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-blue-800">Reverse Winner</dt>
                            <dd class="text-blue-700">The square matching the swapped digits. If score is 7-3, reverse is 3-7.</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-blue-800">Touching Squares</dt>
                            <dd class="text-blue-700">The 4 squares adjacent to the winning square (up, down, left, right with wrap-around). <strong>Each square gets the entered amount</strong>, so total payout is 4× the amount.</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-blue-800">2-Min Warning</dt>
                            <dd class="text-blue-700">Winner based on the score at the 2-minute warning. Only available for Halftime and Final.</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Add New Payout Rule -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Add Payout Rule</h3>
                    <form action="{{ route('manage.boards.payouts.store', $board) }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Period(s)</label>
                                <div class="space-y-1">
                                    @foreach($quarters as $quarter)
                                        <label class="flex items-center">
                                            <input type="checkbox" name="quarters[]" value="{{ $quarter }}"
                                                {{ is_array(old('quarters')) && in_array($quarter, old('quarters')) ? 'checked' : '' }}
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                            <span class="ml-2 text-sm text-gray-700">{{ $quarterLabels[$quarter] ?? $quarter }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <button type="button" onclick="toggleAllPeriods()" id="toggle-periods-btn" class="mt-1 text-xs text-indigo-600 hover:text-indigo-800">Select All</button>
                            </div>
                            <div>
                                <label for="winner_type" class="block text-sm font-medium text-gray-700">Winner Type</label>
                                <select name="winner_type" id="winner_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                    @foreach($winnerTypes as $type)
                                        <option value="{{ $type }}" {{ old('winner_type') === $type ? 'selected' : '' }}>
                                            {{ $winnerTypeLabels[$type] ?? ucfirst($type) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="payout_type" class="block text-sm font-medium text-gray-700">Payout Type</label>
                                <select name="payout_type" id="payout_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                    <option value="fixed" {{ old('payout_type', 'fixed') === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                    <option value="percentage" {{ old('payout_type') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                </select>
                            </div>
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none" id="amount-prefix-container">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <input type="number" name="amount" id="amount" value="{{ old('amount') }}" min="0" step="0.01" class="block w-full pl-7 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none" id="amount-suffix-container" style="display: none;">
                                        <span class="text-gray-500 sm:text-sm" id="amount-suffix">%</span>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500" id="amount-help">e.g., 25.00 for $25</p>
                                <p class="mt-1 text-xs text-amber-600 font-medium" id="touching-warning" style="display: none;">⚠️ Touching pays 4 squares (total = 4× this amount)</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">&nbsp;</label>
                                <button type="submit" class="mt-1 w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Add Rule
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing Payout Rules -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Current Payout Rules</h3>

                    @if($payoutRules->isEmpty())
                        <p class="text-gray-500 text-center py-8">No payout rules have been configured yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Period
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Winner Type
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Payout Type
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Amount
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Est. Payout
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($payoutRules as $rule)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $quarterLabels[$rule->quarter] ?? $rule->quarter }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @php
                                                    $typeColors = [
                                                        'primary' => 'bg-green-100 text-green-800',
                                                        'reverse' => 'bg-purple-100 text-purple-800',
                                                        'touching' => 'bg-blue-100 text-blue-800',
                                                        '2mw' => 'bg-amber-100 text-amber-800',
                                                    ];
                                                @endphp
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeColors[$rule->winner_type] ?? 'bg-gray-100 text-gray-800' }}">
                                                    {{ $winnerTypeLabels[$rule->winner_type] ?? ucfirst($rule->winner_type) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ ucfirst($rule->payout_type) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $rule->amount_display }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @if($rule->winner_type === 'touching')
                                                    ${{ number_format($rule->calculatePayout($potentialPot) / 100, 2) }} × 4 = <span class="font-medium text-gray-900">${{ number_format($rule->calculatePayout($potentialPot) * 4 / 100, 2) }}</span>
                                                @else
                                                    ${{ number_format($rule->calculatePayout($potentialPot) / 100, 2) }}
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <form action="{{ route('manage.boards.payouts.destroy', [$board, $rule]) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this payout rule?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    <script>
        // Quarter restrictions for certain winner types
        const winnerTypeQuarters = {
            '2mw': ['Q2', 'final']
        };

        document.getElementById('payout_type').addEventListener('change', function() {
            const prefix = document.getElementById('amount-prefix-container');
            const suffixContainer = document.getElementById('amount-suffix-container');
            const help = document.getElementById('amount-help');
            const input = document.getElementById('amount');

            if (this.value === 'fixed') {
                prefix.style.display = 'flex';
                suffixContainer.style.display = 'none';
                input.classList.add('pl-7');
                help.textContent = 'e.g., 25.00 for $25';
            } else {
                prefix.style.display = 'none';
                suffixContainer.style.display = 'flex';
                input.classList.remove('pl-7');
                help.textContent = 'e.g., 25 for 25%';
            }
        });

        // Handle winner type restrictions
        document.getElementById('winner_type').addEventListener('change', function() {
            const allowedQuarters = winnerTypeQuarters[this.value];
            const checkboxes = document.querySelectorAll('input[name="quarters[]"]');
            const touchingWarning = document.getElementById('touching-warning');

            // Show/hide touching warning
            if (this.value === 'touching') {
                touchingWarning.style.display = 'block';
            } else {
                touchingWarning.style.display = 'none';
            }

            if (allowedQuarters) {
                // Restrict to allowed quarters only
                checkboxes.forEach(cb => {
                    if (!allowedQuarters.includes(cb.value)) {
                        cb.checked = false;
                        cb.disabled = true;
                        cb.closest('label').classList.add('opacity-50');
                    } else {
                        cb.disabled = false;
                        cb.closest('label').classList.remove('opacity-50');
                    }
                });
            } else {
                // Enable all quarters
                checkboxes.forEach(cb => {
                    cb.disabled = false;
                    cb.closest('label').classList.remove('opacity-50');
                });
            }
            updateToggleButton();
        });

        function toggleAllPeriods() {
            const winnerType = document.getElementById('winner_type').value;
            const allowedQuarters = winnerTypeQuarters[winnerType];
            const checkboxes = document.querySelectorAll('input[name="quarters[]"]:not(:disabled)');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
            updateToggleButton();
        }

        function updateToggleButton() {
            const checkboxes = document.querySelectorAll('input[name="quarters[]"]:not(:disabled)');
            const allChecked = checkboxes.length > 0 && Array.from(checkboxes).every(cb => cb.checked);
            document.getElementById('toggle-periods-btn').textContent = allChecked ? 'Deselect All' : 'Select All';
        }

        // Update button text when individual checkboxes change
        document.querySelectorAll('input[name="quarters[]"]').forEach(cb => {
            cb.addEventListener('change', updateToggleButton);
        });
    </script>
</x-app-layout>
