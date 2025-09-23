<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\Permission\Models\Role;

class AssignDefaultRole
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        /**
         * @var mixed
         */
        $user = $event->user;
        Role::firstOrCreate(['name' => 'officer']);

        if (!$user->hasAnyRole(['officer','admin','user'])) {
            $user->assignRole('officer');
        }
    }
}
