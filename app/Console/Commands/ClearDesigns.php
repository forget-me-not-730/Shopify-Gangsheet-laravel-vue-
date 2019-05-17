<?php

namespace App\Console\Commands;

use App\Models\Design;
use Illuminate\Console\Command;

class ClearDesigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-designs';

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
        Design::whereNull('data')
            ->where('created_at', '<', now()->subDays(1)->format('Y-m-d'))
            ->delete();
    }
}
