<?php

namespace App\Console\Commands;

use App\Models\Font;
use App\Services\FontService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FixFontsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'font:fix {--u=}';

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

        if ($userId) {
            (new FontService())->generateFontCss($userId);
        } else {

            $fonts = Font::all();

            foreach ($fonts as $font) {
                try {
                    $fontFileUrl = str_replace(' ', '%20', $font->file_url);

                    $localPath = Storage::disk('local')->path($font->path);

                    $fileContent = file_get_contents($fontFileUrl);
                    Storage::disk('local')->put($font->path, $fileContent);

                    $f = \FontLib\Font::load($localPath);

                    $font->style = $f->getFontStyle();
                    $font->weight = $f->getFontWeight();

                    $font->save();

                    unlink($localPath);

                    $this->info("Font {$font->id}:{$font->name} updated");
                } catch (\Exception $e) {
                    // ignore
                }
            }

            (new FontService())->generateFontCss();
        }


        $this->info("Success");
    }
}
