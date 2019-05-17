<?php

namespace App\GangSheet\Abstracts;

use App\GangSheet\Exceptions\GenerationException;
use App\Models\Design;

abstract class Printable
{
    protected Design $design;

    protected Generator $generator;

    protected array $options = [];

    public function __construct($design_id)
    {
    }

    static public function create($design_id): static
    {
        return new static($design_id);
    }

    protected function getDesignOptions(): array
    {
        $width = $this->design->getWidth();
        $height = $this->design->getHeight();
        $designUrl = $this->design->getDesignViewUrl();
        $json = $this->design->getDesignJson(false);
        $objects = $json['objects'];
        $productPattern = $this->design->getSetting('productPattern');
        $printPattern = false;
        if ($productPattern && $productPattern['enabled'] && $productPattern['printPattern'])
            $printPattern = true;

        return [
            'design_id' => $this->design->id,
            'width' => $width,
            'height' => $height,
            'designUrl' => $designUrl,
            'printPattern' => $printPattern,
            'objects' => $objects,
        ];
    }

    protected function process(): bool
    {
        if (!storage()->exists($this->design->id)) {
            storage()->makeDirectory($this->design->id);
        }
        $success = $this->generator->build();

        if (config('app.server_name') !== 'local') {
            storage()->deleteDirectory($this->design->id);
        }

        if ($success) {
            $path = $this->design->getGangSheetPath();

            $visibility = 'public';

            if ($this->design->user->isGangSheetPrivate()) {
                $visibility = 'private';
            }

            $this->design->addLog("Uploading gang sheet visibility: {$visibility}, path: {$path}");

            try {
                retry(3, function () use ($path, $visibility) {
                    spaces()->put($path, $this->generator->content, ['visibility' => $visibility]);

                    if (!$this->design->gangSheetExists()) {
                        throw new GenerationException('Gang sheet not found');
                    }
                }, 1000);
            } catch (\Exception $e) {
                return false;
            }

            if ($this->generator->watermark && ($this->design->watermark_path ?? null)) {
                $this->design->addLog("Uploading watermark to: {$this->design->watermark_path}");
                spaces()->put($this->design->watermark_path, $this->generator->watermark);
            }

            return true;
        }

        return false;
    }
}
