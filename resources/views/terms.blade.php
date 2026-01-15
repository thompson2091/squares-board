<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms of Service - SquaresBoard</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gray-50">
    <div class="min-h-screen">
        <nav class="bg-white border-b border-gray-100">
            <div class="max-w-4xl mx-auto px-6 py-4">
                <a href="/" class="text-xl font-bold text-gray-900">
                    <span class="text-violet-600">Squares</span>Board
                </a>
            </div>
        </nav>

        <div class="max-w-4xl mx-auto px-6 py-12">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Terms of Service</h1>

            <div class="bg-white rounded-lg shadow-sm p-8 space-y-6">
                <p class="text-gray-600">Last updated: {{ date('F j, Y') }}</p>

                <div class="space-y-4">
                    <h2 class="text-xl font-semibold text-gray-900">Acceptance of Terms</h2>
                    <p class="text-gray-600">
                        By accessing and using SquaresBoard, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our service.
                    </p>
                </div>

                <div class="space-y-4">
                    <h2 class="text-xl font-semibold text-gray-900">Use of Service</h2>
                    <p class="text-gray-600">
                        SquaresBoard provides a platform for organizing football squares games. You are responsible for ensuring that your use of the service complies with all applicable laws in your jurisdiction.
                    </p>
                </div>

                <div class="space-y-4">
                    <h2 class="text-xl font-semibold text-gray-900">User Accounts</h2>
                    <p class="text-gray-600">
                        You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.
                    </p>
                </div>

                <div class="space-y-4">
                    <h2 class="text-xl font-semibold text-gray-900">Disclaimer</h2>
                    <p class="text-gray-600">
                        SquaresBoard is provided "as is" without warranties of any kind. We are not responsible for any disputes between users regarding payments or game outcomes.
                    </p>
                </div>

                <div class="space-y-4">
                    <h2 class="text-xl font-semibold text-gray-900">Contact Us</h2>
                    <p class="text-gray-600">
                        If you have any questions about these Terms, please contact us at <a href="mailto:thompson2091@gmail.com" class="text-violet-600 hover:text-violet-700">thompson2091@gmail.com</a>.
                    </p>
                </div>
            </div>

            <div class="mt-8 text-center">
                <a href="/" class="text-violet-600 hover:text-violet-700">&larr; Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html>
