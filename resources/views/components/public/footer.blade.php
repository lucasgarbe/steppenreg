<footer class="mt-12">
    <div class="max-w-2xl mx-auto px-4">
        <div class="text-center text-sm text-gray-500 border-t border-gray-200 pt-6">
            <p>&copy; {{ date('Y') }} {{ app(\App\Settings\EventSettings::class)->event_name }}. {{ __('public.footer.privacy') }} | {{ __('public.footer.terms') }}</p>
        </div>
    </div>
</footer>