<?php

namespace App\Console\Commands;

use App\Models\AmortizationSchedules;
use App\Models\Delinquent;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckDelinquentAccounts extends Command
{
    protected $signature = 'check:delinquents';

    protected $description = 'Check for delinquent cooperative loan schedules';

    public function handle()
    {
        $this->info('Checking for delinquent amortization schedules...');

        $now = Carbon::now();
        $schedules = AmortizationSchedules::with('coopProgram')->orderBy('due_date', 'desc')->get();

        $count = 0;

        foreach ($schedules as $schedule) {
            $dueDate = $schedule->due_date ? Carbon::parse($schedule->due_date) : null;
            $datePaid = $schedule->date_paid ? Carbon::parse($schedule->date_paid) : null;

            if (! $dueDate || ! $schedule->coop_program_id) {
                continue;
            }

            // Case 1: Has date paid
            if ($datePaid) {
                $monthsDiff = $dueDate->diffInMonths($datePaid, false);

                if ($datePaid->greaterThan($dueDate) && $monthsDiff >= 4) {
                    $this->markAsDelinquent($schedule, $dueDate, $datePaid);
                    $this->line(" Schedule {$schedule->id} — Paid {$monthsDiff} months late");
                    $count++;
                }
            }

            // Case 2: Not yet paid
            else {
                $monthsDiff = $dueDate->diffInMonths($now, false);

                if ($now->greaterThan($dueDate) && $monthsDiff >= 4) {
                    $this->markAsDelinquent($schedule, $dueDate, null);
                    $this->line(" Schedule {$schedule->id} — Unpaid for {$monthsDiff} months");
                    $count++;
                }
            }
        }

        $this->info(" $count delinquent records identified.");

        return Command::SUCCESS;
    }

    private function markAsDelinquent($schedule, $dueDate, $datePaid)
    {
        Delinquent::updateOrCreate(
            ['amortization_schedule_id' => $schedule->id],
            [
                'coop_program_id' => $schedule->coop_program_id,
                'due_date' => $dueDate,
                'date_paid' => $datePaid,
                'status' => 'Delinquent',
            ]
        );
    }
}
