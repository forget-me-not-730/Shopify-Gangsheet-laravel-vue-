<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UploadDesignHistories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'design:upload';

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
        if (storage()->exists('designs')) {
            $designs = storage()->directories('designs');
            foreach ($designs as $design) {
                try {
                    $histories = storage()->files($design);

                    $designId = basename($design);
                    $lastUpdatedData = null;
                    foreach ($histories as $history) {
                        $data = storage()->get($history);
                        $updatedDate = basename($history);
                        spaces()->put("designs/{$designId}/{$updatedDate}", $data);
                        $lastUpdatedData = $data;
                    }

                    if (!spaces()->exists("designs/{$designId}.json")) {
                        $lastUpdatedData = json_decode($lastUpdatedData, true);
                        spaces()->put("designs/{$designId}.json", json_encode($lastUpdatedData['data']));
                    }

                    storage()->deleteDirectory($design);

                    printf("Design %s uploaded successfully\n", $designId);
                } catch (\Exception $exception) {
                    report($exception);
                }
            }
        }
    }
}
