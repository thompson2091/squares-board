<nav class="bg-primary-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                <a href="/" class="text-white text-2xl font-bold tracking-tight">
                    FreshTrak
                </a>
            </div>

            <div class="hidden md:flex items-center space-x-8">
                <a href="/" class="text-white hover:text-gray-200 transition-colors">
                    Home
                </a>
                <a href="#" class="text-white hover:text-gray-200 transition-colors">
                    Login
                </a>
                <a href="#" class="text-white hover:text-gray-200 transition-colors">
                    Resources
                </a>
                <a href="#" class="text-white hover:text-gray-200 transition-colors">
                    Contact Us
                </a>
            </div>

            <div class="md:hidden">
                <button type="button" class="text-white" id="mobile-menu-button">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div class="hidden md:hidden" id="mobile-menu">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="/" class="block text-white px-3 py-2">Home</a>
            <a href="#" class="block text-white px-3 py-2">Login</a>
            <a href="#" class="block text-white px-3 py-2">Resources</a>
            <a href="#" class="block text-white px-3 py-2">Contact Us</a>
        </div>
    </div>
</nav>
