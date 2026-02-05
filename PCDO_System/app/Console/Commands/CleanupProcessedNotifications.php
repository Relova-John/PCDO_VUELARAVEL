<?php

namespace App\Console\Commands;

use App\Models\Notifications;
use App\Models\PendingNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupProcessedNotifications extends Command
{
    protected $signature = 'notifications:cleanup';

    protected $description = 'Move processed pending notifications to the main notifications table and delete them.';

    public function handle()
    {
        $this->info('--- Starting notifications:cleanup at '.now().' ---');

        DB::beginTransaction();

        try {
            $processed = PendingNotification::where('processed', 1)
                ->lockForUpdate()
                ->get();

            if ($processed->isEmpty()) {
                $this->info('No processed notifications found.');
                DB::commit();

                return;
            }

            foreach ($processed as $pn) {
                // Mark as moving to prevent other workers from touching it
                $pn->update(['processed' => 2]);
                $pn->delete();
            }

            DB::commit();
            $this->info('✅ Processed notifications moved and cleaned up successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('❌ Cleanup failed: '.$e->getMessage());
        }

        $this->info('--- Finished notifications:cleanup at '.now().' ---');
    }
}
