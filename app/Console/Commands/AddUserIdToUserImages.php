<?php

namespace App\Console\Commands;

use App\Models\UserImage;
use Illuminate\Console\Command;

class AddUserIdToUserImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-user-images';

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
            $userImages = UserImage::select(['id', 'category_id', 'user_id'])->with('category:id,user_id')
                ->whereNull('user_id')
                ->withTrashed()
                ->get();

            foreach ($userImages as $image) {
                try {
                    $image->user_id = $image->category->user_id;
                    $image->save();
                } catch (\Exception $exception) {
                    info($exception->getMessage());
                }
            }
        } catch (\Exception $exception) {
            report($exception);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
