<?php

namespace App\Services;

use App\Models\Font;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class FontService
{
    /**
     * Check if a specific font is installed on the system.
     *
     * @param string $fontName The name of the font to check.
     * @return bool True if the font is installed, false otherwise.
     */
    static public function isFontInstalled(string $fontName): bool
    {
        // Replace spaces with '.' to match the fontconfig pattern
        $fontName = str_replace(["'", '"'], '', $fontName);

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Path to the Windows fonts directory
            $fontDir = 'C:\Windows\Fonts';

            // Array of possible font file extensions
            $extensions = ['ttf', 'otf', 'ttc'];

            // Check if any file with the font name and possible extensions exists
            foreach ($extensions as $ext) {
                $fontPath = $fontDir . DIRECTORY_SEPARATOR . $fontName . '.' . $ext;
                if (file_exists($fontPath)) {
                    return true;
                }
            }

            return false;
        } else {
            // fc-list :name=Squealer
            $command = "fc-list | grep '{$fontName}'";

            // Create and run the process
            $process = Process::fromShellCommandline($command);
            $process->run();

            // Check if the process failed
            if (!$process->isSuccessful()) {
                return false;
            }

            // Get the output of the command
            $output = $process->getOutput();

            // Check if the output contains the font name
            return str_contains(strtolower($output), strtolower($fontName));
        }
    }

    static public function installFont($fontName): bool
    {
        if (!self::isFontInstalled($fontName)) {
            try {
                $fonts = Font::where('name', 'like', "%$fontName%")->get();

                foreach ($fonts as $font) {
                    $fontName = $font->name;
                    $fontPath = storage_path("app/" . $font->path);

                    if (!self::isFontInstalled($fontName)) {

                        $fileName = basename($font->file_url);
                        $content = spaces()->get('fonts/' . $fileName);

                        // Ensure the directory exists
                        $directoryPath = dirname($fontPath);
                        if (!file_exists($directoryPath)) {
                            mkdir($directoryPath, 0755, true);
                        }

                        file_put_contents($fontPath, $content);
                    }
                }

                $fontDirectory = storage_path("app/fonts");
                if (file_exists($fontDirectory)) {

                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                        $command = "move $fontDirectory\* C:\Windows\Fonts";

                        $process = Process::fromShellCommandline($command);
                    } else {
                        $command = "mv $fontDirectory/* /usr/share/fonts/custom/ && fc-cache -f -v";

                        $process = Process::fromShellCommandline("sudo -S $command");
                    }

                    $process->run();

                    if ($process->isSuccessful()) {
                        return true;
                    }

                    info($process->getErrorOutput());
                    info('Failed to install fonts for font: ' . $fontName);

                    return false;
                }

                info("Font directory not found");

                return false;
            } catch (\Exception $e) {
                info($e->getMessage());

                return false;
            }
        }

        return true;
    }

    static public function generateFontCss($user_id = null): void
    {
        $fontsCss = '';
        $fontJson = [];

        $fonts = Font::whereHas('userFonts', function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        })
            ->orderBy('name')
            ->get();

        foreach ($fonts as $font) {
            $fontStyle = $font->style;
            $fontWeight = $font->weight;

            $fontName = $font->name;
            $filePath = str_replace("fonts/", '', $font->path);

            $fontsCss .= "
                @font-face {
                    font-family: $fontName;
                    src: url('../$filePath');
                    font-style: $fontStyle;
                    font-weight: $fontWeight;
                }
            ";

            $fontJson[] = [
                'name' => $fontName,
                'style' => $fontStyle,
                'weight' => $fontWeight,
                'url' => $font->file_url,
            ];
        }

        if ($user_id) {
            $shop = User::find($user_id);
            $shop->setSetting('fonts', $fontJson);
        } else {
            option(['fonts' => $fontJson]);
        }

        $fileName = $user_id ?? 'default';

        if (empty($fontsCss)) {
            Storage::disk('spaces')->delete("fonts/css/{$fileName}.css");
        } else {
            Storage::disk('spaces')->put("fonts/css/{$fileName}.css", $fontsCss);
        }
    }

    static public function generateAllFontCss(): void
    {
        $fontsCss = '';
        $nameAndNumbersFontsCss = '';

        $fonts = Font::orderBy('name')
            ->get();

        foreach ($fonts as $font) {
            $fontStyle = $font->style;
            $fontWeight = $font->weight;

            $fontName = $font->name;
            $filePath = str_replace("fonts/", '', $font->path);

            $fontsCss .= "
                @font-face {
                    font-family: $fontName;
                    src: url('../$filePath');
                    font-style: $fontStyle;
                    font-weight: $fontWeight;
                }
            ";

            if ($font->type === 'name_and_number') {
                $nameAndNumbersFontsCss .= "
                    @font-face {
                        font-family: $fontName;
                        src: url('../$filePath');
                        font-style: $fontStyle;
                        font-weight: $fontWeight;
                    }
                ";
            }
        }

        Storage::disk('spaces')->put("fonts/css/all.css", $fontsCss);
        Storage::disk('spaces')->put("fonts/css/name_and_number.css", $nameAndNumbersFontsCss);
    }

    static public function updateGeneralOptions(): void
    {
        $nameAndNumberFonts = Font::where('type', 'name_and_number')->orderBy('name')
            ->get();

        $fonts = [];

        foreach ($nameAndNumberFonts as $font) {
            $fonts[] = [
                'name' => $font->name,
                'style' => $font->style,
                'weight' => $font->weight,
                'url' => $font->file_url,
            ];
        }

        option(['name_and_number_fonts' => $fonts]);
    }
}
