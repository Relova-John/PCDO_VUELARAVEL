<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DatabaseRouter;

class SyncLocalDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:local-database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync local database with remote database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tables = ['workloads','jobs','cooperatives','cooperative_uploads'];
        $anyChanges = false;
        if (DatabaseRouter::getConnection() == 'cloud') {
            foreach ($tables as $table) {
                $localUpdated = DatabaseRouter::syncToLocal($table);
                $cloudUpdated = DatabaseRouter::syncToCloud($table);
                if ($localUpdated || $cloudUpdated) $anyChanges = true;
            }
            if (!$anyChanges) {
                $this->info('No tables have updates or new rows.');
                return;
            }
            $this->info('Cloud → Local sync completed safely.');   
        }
        else {
            $this->info('No connection found with cloud.');
        }
    }

}
