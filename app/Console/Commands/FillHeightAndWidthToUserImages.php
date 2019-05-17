<?php

namespace App\Console\Commands;

use App\Models\UserImage;
use Intervention\Image\Facades\Image as Image;
use Illuminate\Console\Command;

class FillHeightAndWidthToUserImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fill-height-and-width-to-user-images';

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
        try {
            $userImages = UserImage::select(['id', 'user_id', 'original_name'])
                ->whereNull('height')
                ->orWhereNull('width')
                ->where('mime_type', '!=', 'image/svg+xml')
                ->withTrashed()
                ->chunk(1000, function($userImages) {
                    foreach ($userImages as $image) {
                        try {
                            echo $image->original_url . "\n";
        
                            $newImage = Image::make(str_replace('\\', '/', $image->original_url));
                        
                            $imageHeight = $newImage->height();
                            $imageWidth = $newImage->width();

                            echo $imageHeight;
                        
                            $image->height = $imageHeight;
                            $image->width = $imageWidth;
                            $image->save();
                            
                        } catch (\Exception $exception) {
                            info($exception->getMessage());
                        }
                    }
                });
        } catch (\Exception $exception) {
            report($exception);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
