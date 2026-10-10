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
use Illuminate\Support\Facades\Validator;
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
     * Registrations that already have a booked transfer; any further transfer for them is a duplicate.
     *
     * @var list<int>
     */
    public array $registrationIdsWithTransfer = [];

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

        $this->registrationIdsWithTransfer = BankTransferConfirmation::query()
            ->distinct()
            ->orderBy('registration_id')
            ->pluck('registration_id')
            ->all();
    }

    /**
     * Called from the Alpine component to book bank transfers as registration
     * payments (one entry per transfer, see ConfirmPaymentsAction for the rules).
     *
     * Returns the fingerprints that were booked; every other transfer stays
     * unresolved on the client.
     *
     * @param  array<mixed>  $transfers  list of {registration_id, fingerprint}
     * @return array{booked_fingerprints: list<string>}
     */
    public function confirmPayments(array $transfers): array
    {
        $validator = Validator::make(['transfers' => $transfers], [
            'transfers' => ['array'],
            'transfers.*' => ['array'],
            'transfers.*.registration_id' => ['required', 'integer'],
            'transfers.*.fingerprint' => ['nullable', 'string', 'max:128'],
        ]);

        if ($validator->fails()) {
            Notification::make()
                ->title('Invalid transfers')
                ->body('The submitted transfers could not be read. Nothing was booked.')
                ->danger()
                ->send();

            return ['booked_fingerprints' => []];
        }

        $transfers = array_map(fn (array $transfer) => [
            'registration_id' => (int) $transfer['registration_id'],
            'fingerprint' => $transfer['fingerprint'] ?? null,
        ], $transfers);

        try {
            $result = app(ConfirmPaymentsAction::class)->execute($transfers);
        } catch (InvalidRegistrationIdsException $e) {
            Notification::make()
                ->title('Invalid registrations')
                ->body($e->getMessage())
                ->danger()
                ->send();

            Log::warning('Bank import: invalid registration IDs submitted', [
                'message' => $e->getMessage(),
            ]);

            return ['booked_fingerprints' => []];
        }

        $body = "{$result->newly_confirmed} payment(s) confirmed.";
        if ($result->linked > 0) {
            $body .= " {$result->linked} transfer(s) linked to registrations that were already paid.";
        }
        if ($result->already_paid > 0) {
            $body .= " {$result->already_paid} transfer(s) not booked: the registration already has a payment.";
        }

        $notification = Notification::make()->body($body);

        if ($result->already_paid > 0) {
            $notification->title('Some transfers were not booked')->warning();
        } else {
            $notification->title('Payments confirmed')->success();
        }

        $notification->send();

        // Refresh registrations list so the Alpine component gets updated state
        $this->mount();

        return ['booked_fingerprints' => $result->booked_fingerprints];
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
