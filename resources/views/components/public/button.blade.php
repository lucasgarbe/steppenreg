@props([
    'type' => 'button',
    'variant' => 'primary', // primary, secondary, success, warning, danger
    'size' => 'md', // sm, md, lg
    'loading' => false,
    'disabled' => false
])

@php
$baseClasses = 'inline-flex items-center justify-center font-medium rounded-md border focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200';

$variants = [
    'primary' => 'bg-blue-600 hover:bg-blue-700 text-white border-blue-600 focus:ring-blue-500',
    'secondary' => 'bg-white hover:bg-gray-50 text-gray-900 border-gray-300 focus:ring-blue-500',
    'success' => 'bg-green-600 hover:bg-green-700 text-white border-green-600 focus:ring-green-500',
    'warning' => 'bg-yellow-600 hover:bg-yellow-700 text-white border-yellow-600 focus:ring-yellow-500',
    'danger' => 'bg-red-600 hover:bg-red-700 text-white border-red-600 focus:ring-red-500',
];

$sizes = [
    'sm' => 'px-3 py-2 text-sm',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-6 py-3 text-base',
];

$classes = $baseClasses . ' ' . $variants[$variant] . ' ' . $sizes[$size];

if ($disabled || $loading) {
    $classes .= ' opacity-50 cursor-not-allowed';
}
@endphp

<button 
    type="{{ $type }}" 
    {{ $attributes->merge(['class' => $classes]) }}
    @if($disabled || $loading) disabled @endif
>
    @if($loading)
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @endif
    
    {{ $slot }}
</button>