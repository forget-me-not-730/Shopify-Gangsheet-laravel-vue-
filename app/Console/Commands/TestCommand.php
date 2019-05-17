<?php

namespace App\Console\Commands;

use App\GangSheet\GangSheet;
use Illuminate\Console\Command;
use App\Models\Design;
use Illuminate\Support\Facades\DB;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        tools
        {--t=}
        {--dId=}
        {--uId=}
        {--pId=}
        {--oId=}
        {--w=}
        {--h=}
    ';

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
            $tool = $this->option('t');
            $designId = $this->option('dId');
            switch ($tool) {
                case 1:
                    GangSheet::generateByInkscape($designId);
                    dd('done');
                case 2:
                    GangSheet::generateByDusk($designId);
                    dd('done');
                case 3:
                    GangSheet::generateByImagick($designId);
                    dd('done');
                default:
                    $this->testFunction();
            }
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }

    public function testFunction()
    {

    }

    private function recoverDesign(): void
    {
        $designId = "8091ed3e-ccdb-432f-8910-0fd6e57d5a95";

        $design = Design::find($designId);

        if (!$design) {
            $files = spaces()->files("designs/{$designId}");

            $history = [];

            foreach ($files as $file) {
                $path = $file;
                $date = str_replace(".json", "", basename($path));
                $history[] = [
                    'date' => $date,
                    'path' => $path,
                ];
            }

            $latest = collect($history)->sortByDesc('date')->first();

            $data = spaces()->get($latest['path']);
            if ($data) {
                $data = json_decode($data, true);

                $designData = [
                    'id' => $designId,
                    'name' => $data['name'],
                    'user_id' => $data['user_id'],
                    'size_id' => $data['size_id'],
                    'session_id' => $data['session_id'],
                    'quantity' => $data['quantity'],
                    'created_at' => $latest['date'],
                    'updated_at' => $latest['date'],
                    "data" => json_encode([
                        'meta' => [
                            'variant' => $data['data']['meta']['variant']
                        ]
                    ])
                ];

                DB::table('designs')->insert($designData);

                dd('done');
            }
        }
    }
}
