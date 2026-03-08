<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,

    // Domain Service Providers
    App\Domain\BankImport\BankImportServiceProvider::class,
    App\Domain\StartingNumber\StartingNumberServiceProvider::class,
];
