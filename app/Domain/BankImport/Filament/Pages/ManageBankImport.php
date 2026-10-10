<?php

namespace App\Domain\BankImport\Filament\Pages;

use App\Domain\BankImport\Actions\ConfirmPaymentsAction;
use App\Domain\BankImport\Exceptions\InvalidRegistrationIdsException;
use App\Domain\BankImport\Models\BankTransferConfirmation;
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

    protected static string|UnitEnum|null $navigationGroup = 'Bank Import';

    protected static ?string $navigationLabel = 'Bank Transfer Import';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Bank Transfer Import';

    protected static ?string $slug = 'bank-import';

    protected string $view = 'filament.bank-import.pages.manage-bank-import';

    /**
     * All active registrations exposed to the client-side Alpine component.
     *
     * @var array<int, array{id: int, name: string, paid: bool, draw_status: string, track_name: string}>
     */
    public array $registrations = [];

    /**
     * Fingerprints of every transfer confirmed before.
     * Loaded once per mount so the client can pre-check already-handled transfers.
     *
     * @var list<string>
     */
    public array $confirmedFingerprints = [];

    /**
     * Search results for Phase 2 manual resolution, filled by searchRegistrations().
     *
     * @var array<int, array{id: int, name: string, paid: bool, draw_status: string, track_name: string}>
     */
    public array $searchResults = [];

    public function mount(): void
    {
        $this->registrations = Registration::query()
            ->select(['id', 'name', 'track_id'])
            ->with('paymentStatus')
            ->get()
            ->map(fn (Registration $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'paid' => (bool) $r->is_paid,
                'draw_status' => $r->draw_status,
                'track_name' => $r->track_name,
            ])
            ->values()
            ->all();

        $this->confirmedFingerprints = BankTransferConfirmation::query()
            ->pluck('fingerprint')
            ->all();
    }

    /**
     * Called from the Alpine component to mark registrations as paid and
     * record the confirmed transfer fingerprints in the ledger.
     *
     * Returns the ids of registrations that were already paid, so the client
     * keeps those transfers unresolved.
     *
     * @param  array<int>  $ids
     * @param  array<int, string>  $fingerprintsByRegistrationId
     * @return array{already_paid_ids: array<int>}
     */
    public function confirmPayments(array $ids, array $fingerprintsByRegistrationId = []): array
    {
        try {
            $result = app(ConfirmPaymentsAction::class)->execute($ids, $fingerprintsByRegistrationId);

            $body = "{$result->newly_confirmed} payment(s) confirmed.";
            if ($result->already_paid > 0) {
                $body .= " {$result->already_paid} were already marked as paid.";
            }

            Notification::make()
                ->title('Payments confirmed')
                ->body($body)
                ->success()
                ->send();

            // Refresh registrations list so the Alpine component gets updated state
            $this->mount();

            return ['already_paid_ids' => $result->already_paid_ids];
        } catch (InvalidRegistrationIdsException $e) {
            Notification::make()
                ->title('Invalid registrations')
                ->body($e->getMessage())
                ->danger()
                ->send();

            Log::warning('Bank import: invalid registration IDs submitted', [
                'message' => $e->getMessage(),
            ]);

            return ['already_paid_ids' => []];
        }
    }

    /**
     * Search registrations by name for Phase 2 manual resolution.
     * Returns the matching registrations (also stored in $searchResults)
     * so the client-side Alpine component can await the result.
     *
     * @return array<int, array{id: int, name: string, paid: bool, draw_status: string|null, track_name: string|null}>
     */
    public function searchRegistrations(string $query): array
    {
        if (trim($query) === '') {
            $this->searchResults = [];

            return [];
        }

        $this->searchResults = Registration::query()
            ->select(['id', 'name', 'track_id'])
            ->with('paymentStatus')
            ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($query).'%'])
            ->orderBy('name')
            ->get()
            ->map(fn (Registration $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'paid' => (bool) $r->is_paid,
                'draw_status' => $r->draw_status,
                'track_name' => $r->track_name,
            ])
            ->values()
            ->all();

        return $this->searchResults;
    }
}
