<div class="relative min-h-screen">
        <!-- Particle Animation Background -->
        <div class="particles-container">
            <div class="particle small"></div>
            <div class="particle medium"></div>
            <div class="particle large"></div>
            <div class="particle small"></div>
            <div class="particle medium"></div>
            <div class="particle small"></div>
            <div class="particle large"></div>
            <div class="particle medium"></div>
            <div class="particle small"></div>
            <div class="particle large"></div>
            <div class="particle medium"></div>
            <div class="particle small"></div>
            <div class="particle medium"></div>
            <div class="particle large"></div>
            <div class="particle small"></div>
            <div class="particle medium"></div>
            <div class="particle small"></div>
            <div class="particle large"></div>
            <div class="particle medium"></div>
            <div class="particle small"></div>
        </div>

        <!-- Content -->
        <div class="relative z-10 flex flex-col items-center justify-center min-h-screen px-4 lg:px-8">
            <!-- Logo and Header -->
            <div class="text-center mb-12 animate-fade-in" style="margin-top: 5rem;">
                <div class="flex justify-center mb-6">
                    <div class="w-32 h-32">
                        <img src="{{ asset('images/logo.svg') }}" alt="" class="w-full h-full object-contain">
                    </div>
                </div>
                <h1 class="text-5xl font-bold tracking-tight text-base-content lg:text-6xl mb-4">
                    {{ config('app.name') }}
                </h1>
                <p class="text-xl text-base-content/60 max-w-3xl mx-auto">
                    Your ultimate destination for Cataclysm mods and extensions.
                </p>
            </div>

            <!-- Features -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12 max-w-5xl w-full animate-fade-in-delay-1">
                <div class="bg-base-100 p-6 rounded-lg border border-base-content/10 hover:border-primary transition-colors feature-card">
                    <div class="flex items-center mb-4">
                        <x-icon name="search" class="w-6 h-6 text-primary mr-2" />
                        <h3 class="text-lg font-semibold">Discover</h3>
                    </div>
                    <p class="text-base-content/60">
                        Browse through a collection of mods across multiple categories.
                    </p>
                </div>

                <div class="bg-base-100 p-6 rounded-lg border border-base-content/10 hover:border-primary transition-colors feature-card">
                    <div class="flex items-center mb-4">
                        <x-icon name="download" class="w-6 h-6 text-primary mr-2" />
                        <h3 class="text-lg font-semibold">Download</h3>
                    </div>
                    <p class="text-base-content/60">
                        Get easy access to the latest of your favorite mods.
                    </p>
                </div>

                <div class="bg-base-100 p-6 rounded-lg border border-base-content/10 hover:border-primary transition-colors feature-card">
                    <div class="flex items-center mb-4">
                        <x-icon name="upload" class="w-6 h-6 text-primary mr-2" />
                        <h3 class="text-lg font-semibold">Share</h3>
                    </div>
                    <p class="text-base-content/60">
                        Upload your own creations and share them with the community.
                    </p>
                </div>
            </div>

            <!-- CTA Button and Footer -->
            <div class="text-center animate-fade-in-delay-2 relative">
                <a href="{{ route('project-search', \App\Models\ProjectType::first()) }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors pulse-animation">
                    <x-icon name="search" class="w-5 h-5 mr-2" />
                    Browse Mods
                </a>
            </div>

            <!-- Footer for all screen sizes -->
            <div class="w-full py-6 text-base-content/60 animate-fade-in-delay-3">
                <div class="mb-4 flex justify-center space-x-4">
                    <a href="{{ route('login') }}" class="text-primary hover:text-primary-focus transition-colors">Login</a>
                    <a href="{{ route('register') }}" class="text-primary hover:text-primary-focus transition-colors">Register</a>
                </div>
                <div class="flex justify-center mt-20">
                    <x-footer-links />
                </div>
                <div class="mt-10 text-center">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </div>
            </div>
        </div>
    </div>
