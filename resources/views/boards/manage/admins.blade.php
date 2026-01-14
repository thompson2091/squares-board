<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Co-Admins: {{ $board->name }}
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

            <!-- Board Owner -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Board Owner</h3>
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-indigo-500">
                                <span class="text-sm font-medium leading-none text-white">
                                    {{ strtoupper(substr($board->owner?->name ?? 'U', 0, 1)) }}
                                </span>
                            </span>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">{{ $board->owner?->name ?? 'Unknown' }}</div>
                            <div class="text-sm text-gray-500">{{ $board->owner?->email ?? '' }}</div>
                        </div>
                        <div class="ml-auto">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                Owner
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Co-Admin -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Add Co-Admin</h3>
                    <form action="{{ route('manage.boards.admins.store', $board) }}" method="POST">
                        @csrf
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <label for="email" class="sr-only">Email address</label>
                                <input type="email"
                                       name="email"
                                       id="email"
                                       placeholder="Enter user's email address"
                                       value="{{ old('email') }}"
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                       required>
                            </div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Add Co-Admin
                            </button>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            The user must have an existing account. Co-admins can manage payments, enter scores, and configure payouts.
                        </p>
                    </form>
                </div>
            </div>

            <!-- Current Co-Admins -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Current Co-Admins</h3>

                    @if($admins->isEmpty())
                        <p class="text-gray-500 text-center py-8">No co-admins have been added yet.</p>
                    @else
                        <div class="space-y-4">
                            @foreach($admins as $admin)
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-gray-400">
                                                <span class="text-sm font-medium leading-none text-white">
                                                    {{ strtoupper(substr($admin->user?->name ?? 'U', 0, 1)) }}
                                                </span>
                                            </span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $admin->user?->name ?? 'Unknown' }}</div>
                                            <div class="text-sm text-gray-500">{{ $admin->user?->email ?? '' }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <span class="text-xs text-gray-400">
                                            Added {{ $admin->created_at?->format('M j, Y') }}
                                        </span>
                                        <form action="{{ route('manage.boards.admins.destroy', [$board, $admin]) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this co-admin?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Permissions Info -->
            <div class="mt-6 bg-blue-50 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-blue-900 mb-2">Co-Admin Permissions</h3>
                    <ul class="list-disc list-inside text-sm text-blue-700 space-y-1">
                        <li>View and manage payment status for squares</li>
                        <li>Enter and update game scores</li>
                        <li>Configure payout rules</li>
                        <li>View winners and payout calculations</li>
                        <li>Generate row/column numbers</li>
                    </ul>
                    <p class="mt-3 text-sm text-blue-600">
                        <strong>Note:</strong> Co-admins cannot delete the board, transfer ownership, or remove other co-admins.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
