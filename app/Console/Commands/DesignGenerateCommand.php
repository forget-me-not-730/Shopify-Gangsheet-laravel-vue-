<?php

namespace App\Console\Commands;

use App\Jobs\OutputGangSheet;
use Illuminate\Console\Command;

class DesignGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'design:generate {--d=}';

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
        $designId = $this->option('d');

        $success = OutputGangSheet::dispatch($designId)->onConnection('sync');
        if ($success) {
            $this->info('Gang sheet generated successfully');
        } else {
            $this->error('Failed to generate gang sheet');
        }
    }
}
