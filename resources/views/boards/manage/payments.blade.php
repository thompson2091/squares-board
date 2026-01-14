<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Payment Tracking: {{ $board->name }}
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

            <!-- Payment Summary -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Payment Summary</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-500">Total Claimed</div>
                            <div class="text-2xl font-bold text-gray-900">{{ $totalSquares }}</div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="text-sm text-green-600">Paid</div>
                            <div class="text-2xl font-bold text-green-600">{{ $paidSquares }}</div>
                        </div>
                        <div class="bg-red-50 rounded-lg p-4">
                            <div class="text-sm text-red-600">Outstanding</div>
                            <div class="text-2xl font-bold text-red-600">{{ $unpaidSquares }}</div>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="text-sm text-blue-600">Total Collected</div>
                            <div class="text-2xl font-bold text-blue-600">${{ number_format($totalCollected / 100, 2) }}</div>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-sm">
                        <span class="text-gray-500">Expected Total: ${{ number_format($totalExpected / 100, 2) }}</span>
                        <span class="text-gray-500">Outstanding: ${{ number_format($totalOutstanding / 100, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- All Squares -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">All Claimed Squares</h3>
                        <div id="bulk-actions" class="hidden flex items-center gap-4">
                            <span id="selected-count" class="text-sm text-gray-600"></span>
                            <form id="bulk-mark-paid-form" action="{{ route('manage.boards.payments.bulk-mark-paid', $board) }}" method="POST" class="inline hidden">
                                @csrf
                                <div id="bulk-paid-square-ids"></div>
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Mark Selected as Paid
                                </button>
                            </form>
                            <form id="bulk-release-form" action="{{ route('manage.boards.payments.bulk-release', $board) }}" method="POST" class="inline" onsubmit="return confirm('Release selected squares? Users will lose their claims.');">
                                @csrf
                                @method('DELETE')
                                <div id="bulk-release-square-ids"></div>
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Release Selected
                                </button>
                            </form>
                        </div>
                    </div>

                    @if($squares->isEmpty())
                        <p class="text-gray-500 text-center py-8">No squares have been claimed yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left">
                                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Square
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Claimed By
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Amount
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($squares as $square)
                                        <tr class="{{ $square->is_paid ? 'bg-green-50' : '' }}">
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <input type="checkbox"
                                                       class="square-checkbox rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                                       data-square-id="{{ $square->id }}"
                                                       data-is-paid="{{ $square->is_paid ? 'true' : 'false' }}">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm font-medium text-gray-900">
                                                    Row {{ $square->row + 1 }}, Col {{ $square->col + 1 }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $square->user?->name ?? 'Unknown' }}</div>
                                                <div class="text-sm text-gray-500">{{ $square->user?->email ?? '' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($square->is_paid)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Paid
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        Unpaid
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ${{ number_format(($board->price_per_square ?? 0) / 100, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <div class="flex items-center justify-between min-w-[180px]">
                                                    @if($square->is_paid)
                                                        <form action="{{ route('manage.boards.payments.mark-unpaid', [$board, $square]) }}" method="POST" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                                                Mark Unpaid
                                                            </button>
                                                        </form>
                                                    @else
                                                        <form action="{{ route('manage.boards.payments.mark-paid', [$board, $square]) }}" method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit" class="text-green-600 hover:text-green-900">
                                                                Mark Paid
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <form action="{{ route('squares.release', [$board, $square]) }}" method="POST" class="inline" onsubmit="return confirm('Release this square? The user will lose their claim.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                                            Release
                                                        </button>
                                                    </form>
                                                </div>
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
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.square-checkbox');
            const bulkActions = document.getElementById('bulk-actions');
            const selectedCount = document.getElementById('selected-count');
            const bulkPaidSquareIds = document.getElementById('bulk-paid-square-ids');
            const bulkReleaseSquareIds = document.getElementById('bulk-release-square-ids');
            const bulkMarkPaidForm = document.getElementById('bulk-mark-paid-form');

            function updateBulkActions() {
                const checked = document.querySelectorAll('.square-checkbox:checked');
                const allChecked = Array.from(checked);
                const unpaidChecked = allChecked.filter(cb => cb.dataset.isPaid === 'false');

                if (allChecked.length > 0) {
                    bulkActions.classList.remove('hidden');
                    selectedCount.textContent = allChecked.length + ' square(s) selected';

                    // Update hidden inputs for release form (all selected squares)
                    bulkReleaseSquareIds.innerHTML = '';
                    allChecked.forEach(cb => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'square_ids[]';
                        input.value = cb.dataset.squareId;
                        bulkReleaseSquareIds.appendChild(input);
                    });

                    // Update hidden inputs for mark paid form (only unpaid squares)
                    bulkPaidSquareIds.innerHTML = '';
                    if (unpaidChecked.length > 0) {
                        bulkMarkPaidForm.classList.remove('hidden');
                        unpaidChecked.forEach(cb => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'square_ids[]';
                            input.value = cb.dataset.squareId;
                            bulkPaidSquareIds.appendChild(input);
                        });
                    } else {
                        bulkMarkPaidForm.classList.add('hidden');
                    }
                } else {
                    bulkActions.classList.add('hidden');
                }
            }

            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateBulkActions();
            });

            checkboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    const someChecked = Array.from(checkboxes).some(cb => cb.checked);
                    selectAll.checked = allChecked;
                    selectAll.indeterminate = someChecked && !allChecked;
                    updateBulkActions();
                });
            });
        });
    </script>
</x-app-layout>
