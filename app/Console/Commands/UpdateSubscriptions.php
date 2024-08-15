<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\TwitchUser;

class UpdateSubscriptions extends Command
{
    protected $signature = 'subscriptions:update';
    protected $description = 'Update subscriptions by setting sub_activa to 0 if end_sub_date has passed';

    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        // Obtener la fecha actual
        $now = Carbon::now();

        // Encontrar todos los usuarios con end_sub_date pasada
        $users = TwitchUser::where('end_sub_date', '<', $now)
                            ->where('sub_activa', 1)
                            ->get();


        foreach ($users as $user) {
            $user->sub_activa = 0;
            $user->save();
        }

        $this->info('Subscriptions updated successfully.');
    }
}
