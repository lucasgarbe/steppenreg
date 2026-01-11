@php
    $settings = app(\App\Settings\EventSettings::class);
@endphp
<style>
:root {
    --color-spw-yellow: {{ $settings->theme_primary_color }};
    --color-spw-offwhite: {{ $settings->theme_background_color }};
    --color-spw-black: {{ $settings->theme_text_color }};
    --color-spw-purple: {{ $settings->theme_accent_color }};
}
</style>
