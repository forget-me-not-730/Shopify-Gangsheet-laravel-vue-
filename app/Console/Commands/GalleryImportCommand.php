<?php

namespace App\Console\Commands;

use App\Jobs\ImportGoogleDriveImages;
use Illuminate\Console\Command;

class GalleryImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gallery:import {--u=} {--f=}';

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
        $userId = $this->option('u');
        $folderId = $this->option('f');

        ImportGoogleDriveImages::dispatch($folderId, $userId)->onConnection('sync');

        $this->info('Images are being imported.');
    }
}
