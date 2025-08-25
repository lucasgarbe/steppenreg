<header class="mb-8">
    <div class="max-w-2xl mx-auto px-4">
        <!-- Language Switcher -->
        <div class="flex justify-end mb-6">
            <x-language-switcher />
        </div>
        
        <!-- Navigation -->
        @if(!@$hideNavigation)
            <nav class="text-center mb-6">
                <a href="/" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    {{ __('public.navigation.home') }}
                </a>
            </nav>
        @endif
    </div>
</header>