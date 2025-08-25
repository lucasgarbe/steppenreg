@props([
    'title' => null,
    'subtitle' => null,
    'type' => 'default', // default, info, success, warning, error
    'padding' => 'p-6'
])

@php
$typeClasses = [
    'default' => 'bg-gray-50 border-gray-200',
    'info' => 'bg-blue-50 border-blue-200',
    'success' => 'bg-green-50 border-green-200',
    'warning' => 'bg-yellow-50 border-yellow-200',
    'error' => 'bg-red-50 border-red-200',
];

$titleClasses = [
    'default' => 'text-gray-800',
    'info' => 'text-blue-800',
    'success' => 'text-green-800',
    'warning' => 'text-yellow-800',
    'error' => 'text-red-800',
];
@endphp

<div {{ $attributes->merge(['class' => "border rounded-lg {$padding} {$typeClasses[$type]}"]) }}>
    @if($title || $subtitle)
        <div class="mb-4">
            @if($title)
                <h2 class="text-lg font-semibold {{ $titleClasses[$type] }} mb-2">{{ $title }}</h2>
            @endif
            @if($subtitle)
                <p class="text-sm {{ $titleClasses[$type] }} opacity-75">{{ $subtitle }}</p>
            @endif
        </div>
    @endif
    
    {{ $slot }}
</div>