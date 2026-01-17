<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Squares Board - The Modern Way to Play Football Squares</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" href="/favicon.ico">

    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="SquaresBoard - Football Squares, Simplified">
    <meta property="og:description" content="Create and manage your game day squares pool in minutes. No spreadsheets, no hassle.">
    <meta property="og:image" content="{{ url('/og-image.svg') }}">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="SquaresBoard - Football Squares, Simplified">
    <meta name="twitter:description" content="Create and manage your game day squares pool in minutes. No spreadsheets, no hassle.">
    <meta name="twitter:image" content="{{ url('/og-image.svg') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-white">
    {{-- Navigation --}}
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-100">
        <div class="max-w-6xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <a href="/" class="text-xl font-bold text-gray-900">
                    <span class="text-violet-600">Squares</span>Board
                </a>
                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
                            Sign In
                        </a>
                        <a href="{{ route('register') }}" class="text-sm font-medium text-white bg-violet-600 hover:bg-violet-700 px-4 py-2 rounded-lg transition-colors">
                            Get Started
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Hero Section --}}
    <section class="min-h-screen flex items-center justify-center pt-20 relative overflow-hidden">
        {{-- Background decoration --}}
        <div class="absolute inset-0 -z-10">
            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-violet-200 rounded-full blur-3xl opacity-30"></div>
            <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-fuchsia-200 rounded-full blur-3xl opacity-30"></div>
        </div>

        <div class="max-w-4xl mx-auto px-6 py-20 text-center">
            {{-- Badge --}}
            <div class="inline-flex items-center gap-2 bg-violet-50 text-violet-700 px-4 py-2 rounded-full text-sm font-medium mb-8">
                <span class="w-2 h-2 bg-violet-500 rounded-full animate-pulse"></span>
                Free to use, no credit card required
            </div>

            {{-- Main headline --}}
            <h1 class="text-5xl md:text-6xl lg:text-7xl font-extrabold text-gray-900 mb-6 leading-tight tracking-tight">
                Football Squares,
                <span class="bg-gradient-to-r from-violet-600 to-fuchsia-600 bg-clip-text text-transparent">Simplified</span>
            </h1>

            {{-- Subheadline --}}
            <p class="text-xl md:text-2xl text-gray-500 max-w-2xl mx-auto mb-12 leading-relaxed">
                Create and manage your game day squares pool in minutes. No spreadsheets, no hassle.
            </p>

            {{-- CTAs --}}
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}"
                   class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white bg-violet-600 rounded-xl shadow-lg shadow-violet-500/25 hover:bg-violet-700 hover:shadow-violet-500/40 transition-all duration-200">
                    Create a Squares Board
                    <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </a>
            </div>

            {{-- Visual grid preview --}}
            <div class="mt-20 relative">
                <div class="absolute inset-0 bg-gradient-to-t from-white via-transparent to-transparent z-10 pointer-events-none"></div>
                <div class="grid grid-cols-10 gap-1 max-w-md mx-auto opacity-60">
                    @for($i = 0; $i < 100; $i++)
                        @php
                            $colors = ['bg-violet-100', 'bg-fuchsia-100', 'bg-violet-200', 'bg-gray-100'];
                            $randomColor = $colors[array_rand($colors)];
                        @endphp
                        <div class="aspect-square {{ $randomColor }} rounded-sm"></div>
                    @endfor
                </div>
            </div>
        </div>
    </section>

    {{-- Features Section --}}
    <section class="py-24 bg-gray-50">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Everything you need for game day
                </h2>
                <p class="text-lg text-gray-500 max-w-xl mx-auto">
                    Simple, powerful features that make running your squares pool effortless
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                {{-- Feature 1 --}}
                <div class="bg-white p-8 rounded-2xl shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-violet-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Easy Setup</h3>
                    <p class="text-gray-500">
                        Create your board in seconds. Set your price per square, customize payouts, and you're ready to go.
                    </p>
                </div>

                {{-- Feature 2 --}}
                <div class="bg-white p-8 rounded-2xl shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-fuchsia-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-fuchsia-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Share Instantly</h3>
                    <p class="text-gray-500">
                        Send a link to friends and family. They can claim squares with one click - no account required.
                    </p>
                </div>

                {{-- Feature 3 --}}
                <div class="bg-white p-8 rounded-2xl shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Auto Payouts</h3>
                    <p class="text-gray-500">
                        Numbers are randomly assigned. Winners are calculated automatically at each quarter.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="py-24">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Three steps to kickoff
                </h2>
            </div>

            <div class="grid md:grid-cols-3 gap-12">
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-violet-500 to-fuchsia-500 rounded-2xl flex items-center justify-center mx-auto mb-6 text-white text-2xl font-bold shadow-lg">
                        1
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Create</h3>
                    <p class="text-gray-500">Set up your board with custom pricing and payout rules</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-violet-500 to-fuchsia-500 rounded-2xl flex items-center justify-center mx-auto mb-6 text-white text-2xl font-bold shadow-lg">
                        2
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Share</h3>
                    <p class="text-gray-500">Invite players to pick their squares with a simple link</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-violet-500 to-fuchsia-500 rounded-2xl flex items-center justify-center mx-auto mb-6 text-white text-2xl font-bold shadow-lg">
                        3
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Play</h3>
                    <p class="text-gray-500">Lock the board, reveal numbers, and watch the game</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="py-24 bg-gradient-to-br from-violet-600 to-fuchsia-600">
        <div class="max-w-4xl mx-auto px-6 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
                Ready for game day?
            </h2>
            <p class="text-xl text-violet-100 mb-10 max-w-xl mx-auto">
                Create your squares board in minutes and make this year's game unforgettable.
            </p>
            <a href="{{ route('register') }}"
               class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-violet-600 bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-200">
                Get Started Free
                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="py-12 bg-gray-900">
        <div class="max-w-6xl mx-auto px-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="text-gray-400 text-sm">
                    &copy; {{ date('Y') }} SquaresBoard. All rights reserved.
                </div>
                <div class="flex items-center gap-6 text-sm text-gray-400">
                    <a href="{{ route('privacy') }}" class="hover:text-white transition-colors">Privacy</a>
                    <a href="{{ route('terms') }}" class="hover:text-white transition-colors">Terms</a>
                    <a href="mailto:thompson2091@gmail.com" class="hover:text-white transition-colors">Contact</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
