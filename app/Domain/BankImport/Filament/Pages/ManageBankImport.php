<?php

namespace App\Domain\BankImport\Filament\Pages;

use App\Domain\BankImport\Actions\ConfirmPaymentsAction;
use App\Domain\BankImport\Exceptions\InvalidRegistrationIdsException;
use App\Models\Registration;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class ManageBankImport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Registration';

    protected static ?string $navigationLabel = 'Bank Transfer Import';

    protected static ?int $navigationSort = 40;

    protected static ?string $title = 'Bank Transfer Import';

    protected static ?string $slug = 'bank-import';

    protected string $view = 'filament.bank-import.pages.manage-bank-import';

    /**
     * All active registrations exposed to the client-side Alpine component.
     *
     * @var array<int, array{id: int, name: string, payed: bool, draw_status: string, track_name: string}>
     */
    public array $registrations = [];

    /**
     * Search results for Phase 2 manual resolution.
     *
     * @var array<int, array{id: int, name: string, payed: bool, draw_status: string, track_name: string}>
     */
    public array $searchResults = [];

    public function mount(): void
    {
        $this->registrations = Registration::query()
            ->select(['id', 'name', 'payed', 'draw_status', 'track_id'])
            ->get()
            ->map(fn (Registration $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'payed' => (bool) $r->payed,
                'draw_status' => $r->draw_status,
                'track_name' => $r->track_name,
            ])
            ->values()
            ->all();
    }

    /**
     * Called from the Alpine component via Livewire.dispatch to mark registrations as payed.
     *
     * @param  array<int>  $ids
     */
    public function confirmPayments(array $ids): void
    {
        try {
            $result = app(ConfirmPaymentsAction::class)->execute($ids);

            $body = "{$result->newly_confirmed} payment(s) confirmed.";
            if ($result->already_payed > 0) {
                $body .= " {$result->already_payed} were already marked as paid.";
            }

            Notification::make()
                ->title('Payments confirmed')
                ->body($body)
                ->success()
                ->send();

            // Refresh registrations list so the Alpine component gets updated state
            $this->mount();

        } catch (InvalidRegistrationIdsException $e) {
            Notification::make()
                ->title('Invalid registrations')
                ->body($e->getMessage())
                ->danger()
                ->send();

            Log::warning('Bank import: invalid registration IDs submitted', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Search registrations by name for Phase 2 manual resolution.
     * Results are stored in $searchResults for the view to consume.
     */
    public function searchRegistrations(string $query): void
    {
        if (trim($query) === '') {
            $this->searchResults = [];

            return;
        }

        $this->searchResults = Registration::query()
            ->select(['id', 'name', 'payed', 'draw_status', 'track_id'])
            ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($query).'%'])
            ->orderBy('name')
            ->get()
            ->map(fn (Registration $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'payed' => (bool) $r->payed,
                'draw_status' => $r->draw_status,
                'track_name' => $r->track_name,
            ])
            ->values()
            ->all();
    }
}
