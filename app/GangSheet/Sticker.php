<?php

namespace App\GangSheet;

use App\GangSheet\Abstracts\Printable;
use App\GangSheet\Exceptions\GenerationException;
use App\GangSheet\Generators\StickerGenerator;
use App\Models\Design;

class Sticker extends Printable
{
    public function __construct($design_id)
    {
        parent::__construct($design_id);

        $this->design = Design::withTrashed()->findOrFail($design_id);

        $this->options = $this->getDesignOptions();
    }

    protected function getDesignOptions(): array
    {
        $options = parent::getDesignOptions();

        $fileExtension = $this->design->getSetting('stickerFileExtension', '.pdf');
        $stickerOutlineWidth = $this->design->getSetting('stickerOutlineWidth', 1);
        $stickerOutlineColor = $this->design->getSetting('stickerOutlineColor', '#000000');

        return array_merge($options, [
            'fileExtension' => $fileExtension,
            'stickerOutlineWidth' => $stickerOutlineWidth,
            'stickerOutlineColor' => $stickerOutlineColor,
        ]);
    }

    /**
     * @throws GenerationException
     */
    protected function createGenerator(): static
    {
        $this->generator = new StickerGenerator($this->options);

        $this->generator->setLogger($this->design);

        return $this;
    }

    public function generate(): bool
    {
        try {
            $this->design->setMetaData([
                'generation_server' => config('app.server_name'),
            ]);

            $this->design->update([
                'status' => Design::STATUS_PROCESSING,
                'file_name' => null
            ]);

            $success = $this->createGenerator()
                ->process();

            if ($success) {
                $this->design->addLog("Sticker generation completed");
                $this->design->update(['status' => Design::STATUS_COMPLETED]);
                $this->design->uploadGangSheet();
            } else {
                $this->design->addLog("Sticker generation failed");
                $this->design->update([
                    'status' => Design::STATUS_FAILED,
                    'file_name' => null
                ]);
            }

            return $success;
        } catch (\Exception $e) {
            $this->design->update([
                'status' => Design::STATUS_FAILED,
                'file_name' => null
            ]);

            return false;
        }
    }
}
