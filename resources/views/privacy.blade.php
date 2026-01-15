<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy - SquaresBoard</title>
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
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Privacy Policy</h1>

            <div class="bg-white rounded-lg shadow-sm p-8 space-y-6">
                <p class="text-gray-600">Last updated: {{ date('F j, Y') }}</p>

                <div class="space-y-4">
                    <h2 class="text-xl font-semibold text-gray-900">Information We Collect</h2>
                    <p class="text-gray-600">
                        We collect only the minimum information necessary to provide our service, including your name and email address when you create an account.
                    </p>
                </div>

                <div class="space-y-4">
                    <h2 class="text-xl font-semibold text-gray-900">How We Use Your Information</h2>
                    <p class="text-gray-600">
                        Your information is used solely to operate the SquaresBoard service and allow you to participate in squares games. We do not sell, trade, or share your personal information with third parties.
                    </p>
                </div>

                <div class="space-y-4">
                    <h2 class="text-xl font-semibold text-gray-900">Data Security</h2>
                    <p class="text-gray-600">
                        We implement appropriate security measures to protect your personal information. However, no method of transmission over the internet is 100% secure.
                    </p>
                </div>

                <div class="space-y-4">
                    <h2 class="text-xl font-semibold text-gray-900">Contact Us</h2>
                    <p class="text-gray-600">
                        If you have any questions about this Privacy Policy, please contact us at <a href="mailto:thompson2091@gmail.com" class="text-violet-600 hover:text-violet-700">thompson2091@gmail.com</a>.
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
