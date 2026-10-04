<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,

    // Domain Service Providers
    App\Domain\Payment\PaymentServiceProvider::class,
    App\Domain\StartingNumber\StartingNumberServiceProvider::class,
    App\Domain\Mail\MailServiceProvider::class,
];
