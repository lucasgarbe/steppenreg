<?php

namespace App\Domain\BankImport;

use App\Domain\BankImport\Actions\ConfirmPaymentsAction;
use App\Domain\BankImport\Filament\Pages\ManageBankImport;
use Filament\Panel;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class BankImportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ConfirmPaymentsAction::class);

        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() !== 'admin') {
                return;
            }

            $panel->pages([
                ManageBankImport::class,
            ]);
        });
    }

    public function boot(): void
    {
        FilamentAsset::register([
            Js::make('bank-import', Vite::asset('resources/js/bank-import/index.js'))->module(),
        ]);
    }
}
