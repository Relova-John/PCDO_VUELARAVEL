<?php

namespace App\Console\Commands;

use App\Models\PendingNotification;
use App\Notifications\LoanOverdueNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class ProcessPendingNotifications extends Command
{
    protected $signature = 'notifications:process';

    protected $description = 'Process pending loan notifications and send emails';

    public function handle()
    {
        $this->info('--- Starting notifications:process at '.now().' ---');

        // Use transaction and locking to avoid race conditions
        DB::beginTransaction();

        $notifications = PendingNotification::where('processed', 0)
            ->lockForUpdate()
            ->get();

        if ($notifications->isEmpty()) {
            $this->info('No pending notifications to process.');
            DB::commit();

            return SymfonyCommand::SUCCESS;
        }

        foreach ($notifications as $notif) {
            // Double-check if already processed in another thread
            if ($notif->processed) {
                $this->warn("Skipping already processed notification ID {$notif->id}");

                continue;
            }

            $schedule = $notif->schedule;

            if (! $schedule) {
                $this->error("Schedule ID {$notif->schedule_id} not found.");

                continue;
            }

            $coopProgram = $schedule->coopProgram;
            $email = $coopProgram?->coopDetail->email;
            $coopName = $coopProgram?->cooperative->name ?? 'Unknown Coop Program';

            if (! $email) {
                $this->warn("No email found for coop program ID {$schedule->coop_program_id}");

                continue;
            }

            try {
                $notification = new LoanOverdueNotification($schedule);
                $notification->toMail($schedule);

                Notification::route('mail', $email)->notify($notification);

                // Mark processed with content
                $notif->update([
                    'processed' => 1,
                ]);

                $this->info("✅ Notification sent to {$email} for {$coopName}, type: {$notif->type}");
            } catch (\Exception $e) {
                $this->error("❌ Failed to send notification to {$email}: ".$e->getMessage());
            }
        }

        DB::commit();
        $this->info('--- Finished notifications:process at '.now().' ---');

        return SymfonyCommand::SUCCESS;
    }
}
