<div class="relative inline-block">
    <div class="flex items-center space-x-2">
        @foreach($languages as $locale => $language)
            <a
                href="{{ request()->fullUrlWithQuery(['locale' => $locale]) }}"
                class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 {{ $locale === $currentLocale ? 'bg-spw-yellow text-spw-black' : 'text-gray-700 hover:bg-gray-100' }}"
                title="{{ $language['name'] }}"
            >
                <span class="mr-2 px-1 text-xs bg-gray-200 rounded">{{ $language['flag'] }}</span>
                <span>{{ $language['name'] }}</span>
            </a>
        @endforeach
    </div>
</div>
