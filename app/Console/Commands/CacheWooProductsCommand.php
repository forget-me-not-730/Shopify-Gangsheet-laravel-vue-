<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CacheWooProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:cache';

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
        User::where('type', 'woo')
            ->where('status', 'active')
            ->chunkById(50, function ($shops) {
                foreach ($shops as $shop) {
                    $this->info('Caching products for shop: ' . $shop->id);
                    $shop->cacheProducts();
                    $this->info("Products cached for shop: {$shop->id}");
                }
            });
    }
}
