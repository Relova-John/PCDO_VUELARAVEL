<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AmortizationSchedules;
use Carbon\Carbon;

class ProcessOverdueSchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:overdue-schedules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();

        $overdueSchedules = AmortizationSchedules::whereDate('due_date', '<', $today)
            ->where('status', '!=', 'Paid')
            ->where('overdue_processed', false)
            ->get();

        foreach ($overdueSchedules as $schedule) {

            $remainingBalance = ($schedule->current_balance) - ($schedule->amount_paid ?? 0);
            $penaltyAmount = ($schedule->coopProgram->program->penalty / 100) * abs($schedule->installment ?? 0);

            if ($remainingBalance <= 0) {
                $schedule->overdue_processed = true;
                $schedule->save();
                continue;
            }

            // Get next schedule
            $nextSchedule = AmortizationSchedules::where('coop_program_id', $schedule->coop_program_id)
                ->whereDate('due_date', '>', $schedule->due_date)
                ->orderBy('due_date', 'asc')
                ->first();

            if ($nextSchedule) {
                // 1️⃣ Add remaining balance to next current_balance
                $nextSchedule->current_balance += $remainingBalance + $penaltyAmount;
                $nextSchedule->save();

            } else {
                // 2️⃣ If last schedule → create new one
                $nextDueDate = Carbon::parse($schedule->due_date)->addMonth();

                AmortizationSchedules::create([
                    'coop_program_id' => $schedule->coop_program_id,
                    'current_balance' => $remainingBalance,
                    'amount_paid' => 0,
                    'balance' => 0,
                    'penalty_amount' => 0,
                    'status' => 'Unpaid',
                    'installment' => 0,
                    'due_date' => $nextDueDate,
                    'overdue_processed' => false,
                ]);
            }

            // Mark as processed (VERY IMPORTANT)
            $schedule->overdue_processed = true;
            $schedule->penalty_amount = $penaltyAmount;
            $schedule->status = 'Overdue';
            $schedule->save();
        }

        $this->info('Overdue schedules processed successfully.');
    }
}
