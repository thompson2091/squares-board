@props(['title' => 'Welcome to the Service Portal', 'subtitle' => null])

<section class="bg-gradient-to-r from-primary to-primary-dark py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
            {{ $title }}
        </h1>
        @if($subtitle)
            <p class="text-xl text-white text-opacity-90 max-w-2xl mx-auto">
                {{ $subtitle }}
            </p>
        @endif
    </div>
</section>
