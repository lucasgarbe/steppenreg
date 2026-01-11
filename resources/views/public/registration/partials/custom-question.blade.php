@php
    use App\Services\MarkdownRenderer;
    
    $key = $question['key'];
    $type = $question['type'];
    $trans = $question['translations'][app()->getLocale()] ?? $question['translations']['en'] ?? [];
    $label = $trans['label'] ?? $key;
    $placeholder = $trans['placeholder'] ?? '';
    $help = $trans['help'] ?? '';
    $required = $question['required'] ?? false;
    $options = $question['options'] ?? [];
    $markdownRenderer = app(MarkdownRenderer::class);
@endphp

<div>
    <label for="custom_{{ $key }}" class="block text-sm font-medium text-gray-700 mb-1 [&_a]:text-blue-600 [&_a]:underline [&_a:hover]:text-blue-800">
        {!! $markdownRenderer->render($label) !!}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>

    @switch($type)
        @case('text')
        @case('email')
        @case('number')
            <input
                type="{{ $type }}"
                id="custom_{{ $key }}"
                name="custom_answers[{{ $key }}]"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-spw-yellow focus:border-spw-yellow"
                placeholder="{{ $placeholder }}"
                value="{{ old('custom_answers.' . $key) }}"
                {{ $required ? 'required' : '' }}
            >
            @break

        @case('textarea')
            <textarea
                id="custom_{{ $key }}"
                name="custom_answers[{{ $key }}]"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-spw-yellow focus:border-spw-yellow"
                placeholder="{{ $placeholder }}"
                {{ $required ? 'required' : '' }}
            >{{ old('custom_answers.' . $key) }}</textarea>
            @break

        @case('select')
            <select
                id="custom_{{ $key }}"
                name="custom_answers[{{ $key }}]"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-spw-yellow focus:border-spw-yellow"
                {{ $required ? 'required' : '' }}
            >
                <option value="">{{ $placeholder ?: __('public.registration.fields.select_option') }}</option>
                @foreach($options as $option)
                    <option
                        value="{{ $option['value'] }}"
                        {{ old('custom_answers.' . $key) == $option['value'] ? 'selected' : '' }}
                    >
                        {{ $option['label_' . app()->getLocale()] ?? $option['label_en'] ?? $option['value'] }}
                    </option>
                @endforeach
            </select>
            @break

        @case('radio')
            <div class="mt-2 space-y-2">
                @foreach($options as $option)
                    <label class="flex items-center cursor-pointer">
                        <input
                            type="radio"
                            name="custom_answers[{{ $key }}]"
                            value="{{ $option['value'] }}"
                            class="mr-2 text-spw-yellow focus:ring-spw-yellow"
                            {{ old('custom_answers.' . $key) == $option['value'] ? 'checked' : '' }}
                            {{ $required ? 'required' : '' }}
                        >
                        <span class="text-sm text-gray-700">
                            {{ $option['label_' . app()->getLocale()] ?? $option['label_en'] ?? $option['value'] }}
                        </span>
                    </label>
                @endforeach
            </div>
            @break

        @case('checkbox')
            <div class="mt-2 space-y-2">
                @foreach($options as $option)
                    <label class="flex items-center cursor-pointer">
                        <input
                            type="checkbox"
                            name="custom_answers[{{ $key }}][]"
                            value="{{ $option['value'] }}"
                            class="mr-2 text-spw-yellow focus:ring-spw-yellow rounded"
                            {{ in_array($option['value'], (array) old('custom_answers.' . $key, [])) ? 'checked' : '' }}
                        >
                        <span class="text-sm text-gray-700">
                            {{ $option['label_' . app()->getLocale()] ?? $option['label_en'] ?? $option['value'] }}
                        </span>
                    </label>
                @endforeach
            </div>
            @break

        @case('date')
            <input
                type="date"
                id="custom_{{ $key }}"
                name="custom_answers[{{ $key }}]"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-spw-yellow focus:border-spw-yellow"
                value="{{ old('custom_answers.' . $key) }}"
                {{ $required ? 'required' : '' }}
            >
            @break
    @endswitch

    @if($help)
        <div class="mt-1 text-xs text-gray-500 [&_a]:text-blue-600 [&_a]:underline [&_a:hover]:text-blue-800">{!! $markdownRenderer->render($help) !!}</div>
    @endif

    @error('custom_answers.' . $key)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
