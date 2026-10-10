<?php

namespace App\Domain\BankImport;

use App\Domain\BankImport\Actions\ConfirmPaymentsAction;
use App\Domain\BankImport\Filament\Pages\ManageBankImport;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Foundation\Vite;
use Illuminate\Support\ServiceProvider;

class BankImportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ConfirmPaymentsAction::class);

        if (! config('steppenreg.features.bank_import', true)) {
            return;
        }

        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() !== 'admin') {
                return;
            }

            $panel->pages([
                ManageBankImport::class,
            ]);

            $panel->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn (): Htmlable => app(Vite::class)('resources/js/bank-import/index.js'),
            );
        });
    }
}
