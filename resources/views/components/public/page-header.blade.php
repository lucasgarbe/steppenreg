@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'iconClass' => 'text-blue-600',
    'iconBackground' => 'bg-blue-100'
])

<div class="text-center mb-8">
    @if($icon)
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full {{ $iconBackground }} mb-6">
            {!! $icon !!}
        </div>
    @endif
    
    <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $title }}</h1>
    
    @if($subtitle)
        <p class="text-gray-600">{{ $subtitle }}</p>
    @endif
    
    {{ $slot }}
</div>