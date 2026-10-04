<?php

namespace Database\Seeders;

use App\Domain\Draw\Models\DrawStatus;
use App\Models\Registration;
use App\Models\Team;
use App\Settings\EventSettings;
use Illuminate\Database\Seeder;

class DrawRehearsalSeeder extends Seeder
{
    /**
     * Seed realistic draw rehearsal data for local testing.
     *
     * Wipes registrations, teams and draw statuses, then creates a defined
     * population on the first configured track: 8 teams (35 members) plus
     * 65 individuals = 100 entries, none drawn yet. Registration events are
     * suppressed so no confirmation mails get queued.
     */
    public function run(): void
    {
        $trackId = (int) app(EventSettings::class)->tracks[0]['id'];

        $teamSizes = collect([5, 4, 6, 3, 4, 5, 2, 6]);
        $teamMemberCount = 0;
        $individualCount = 0;

        DrawStatus::query()->delete();
        Registration::withoutEvents(fn () => Registration::query()->forceDelete());
        Team::withTrashed()->forceDelete();

        foreach ($teamSizes as $index => $size) {
            $team = Team::create([
                'name' => 'Rehearsal Team '.($index + 1),
                'max_members' => $size,
                'track_id' => $trackId,
            ]);

            foreach (range(1, $size) as $member) {
                $this->createRegistration($trackId, $team->id, $teamMemberCount);
                $teamMemberCount++;
            }
        }

        foreach (range(1, 100 - $teamMemberCount) as $unused) {
            $this->createRegistration($trackId, null, $teamMemberCount + $individualCount);
            $individualCount++;
        }

        $this->command->info(sprintf(
            'Draw rehearsal data: %d registrations on track %d (%d team members in %d teams, %d individuals), no draw statuses yet.',
            $teamMemberCount + $individualCount,
            $trackId,
            $teamMemberCount,
            $teamSizes->count(),
            $individualCount,
        ));
    }

    protected function createRegistration(int $trackId, ?int $teamId, int $index): void
    {
        $registration = Registration::create([
            'name' => 'Rehearsal Participant '.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
            'email' => 'rehearsal-'.($index + 1).'@example.com',
            'track_id' => $trackId,
            'team_id' => $teamId,
            'age' => random_int(16, 70),
            'gender' => ['flinta', 'all_gender'][random_int(0, 1)],
        ]);

        app(PaymentService::class)->markPaid($registration, 'seed');
    }
}
