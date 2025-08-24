<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LanguageSwitcher extends Component
{
    public array $languages;
    public string $currentLocale;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->languages = config('app.supported_locales', [
            'de' => ['name' => 'Deutsch', 'flag' => 'DE'],
            'en' => ['name' => 'English', 'flag' => 'EN'],
        ]);
        $this->currentLocale = app()->getLocale();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.language-switcher');
    }
}
