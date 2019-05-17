<?php

namespace App\Console\Commands;

use App\Models\Design;
use Illuminate\Console\Command;

class OptimizeDesignData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:optimize-design-data';

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
        Design::chunkById(500, function ($designs) {
            foreach ($designs as $design) {
                $data = $design->data;

                if (!empty($data['raw'])) {
                    $data = $data['raw'];
                }

                $data['designId'] = $design->id;

                $design->data = $data;

                $design->save();

                $this->alert($design->id);
            }
        });
    }
}
