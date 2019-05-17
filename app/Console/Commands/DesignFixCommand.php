<?php

namespace App\Console\Commands;

use App\Models\Design;
use App\Services\SvgService;
use Illuminate\Console\Command;
use \Illuminate\Support\Facades\Storage;

class DesignFixCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'design:fix {--d=}';

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

        $design = Design::find($designId);

        $design->fixDesignSvgImages();

        dd('done');
    }
}
