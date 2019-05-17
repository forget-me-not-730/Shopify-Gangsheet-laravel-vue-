<?php

namespace App\GangSheet;

use App\GangSheet\Abstracts\Printable;
use App\GangSheet\Exceptions\GenerationException;
use App\GangSheet\Generators\DuskGenerator;
use App\GangSheet\Generators\ImagickGenerator;
use App\GangSheet\Generators\InkscapeGenerator;
use App\Models\Design;

class GangSheet extends Printable
{
    protected const METHOD_IMAGICK = 'imagick';

    protected const METHOD_INKSCAPE = 'inkscape';

    protected const METHOD_DUSK = 'dusk';

    public function __construct($design_id)
    {
        parent::__construct($design_id);

        $this->design = Design::withTrashed()->findOrFail($design_id);

        $this->options = $this->getDesignOptions();
    }

    protected function getDesignOptions(): array
    {
        $options = parent::getDesignOptions();

        $actualHeight = $this->design->getActualHeight();
        $printFileName = $this->design->getPrintFileName();
        $fileName = $this->design->getGangSheetFileName();
        $autoTrimGangSheet = $this->design->getSetting('autoTrimGangSheet', true);
        $printFileNamePosition = $this->design->getSetting('printFileNamePosition', 'top');
        $fileNameHeight = $this->design->getSetting('printFileNameHeight', 1);
        $fileExtension = $this->design->getSetting('gangSheetFileExtension', '.png');

        $printShopQRLogo = $this->design->getSetting('printShopQRLogo', false);
        $printShopQRLogoPosition = $this->design->getSetting('printShopQRLogoPosition', 'inline');
        $printShopQROrLogo = $this->design->getSetting('printShopQROrLogo', 'qr');
        $printShopQRLogoSize = $this->design->getSetting('printShopQRLogoSize', 2);
        $printShopQR = $this->design->getSetting('printShopQR', 'Gang Sheet');
        $printShopLogo = $this->design->getSetting('printShopLogo', $this->design->user()->logo_url ?? null);

        $generateWatermark = false;

        if ($this->design->watermark_path ?? null) {
            $generateWatermark = true;
        }

        $printQRLogo = [
            'enable' => $printShopQRLogo,
            'position' => $printShopQRLogoPosition,
            'type' => $printShopQROrLogo,
            'size' => $printShopQRLogoSize,
            'qr' => $printShopQR,
            'logo' => $printShopLogo,
        ];

        return array_merge($options, [
            'actualHeight' => $actualHeight,
            'printFileName' => $printFileName,
            'fileName' => $fileName,
            'printFileNamePosition' => $printFileNamePosition,
            'fileNameHeight' => $fileNameHeight,
            'autoTrimGangSheet' => $autoTrimGangSheet,
            'fileExtension' => $fileExtension,
            'printQRLogo' => $printQRLogo,
            'generateWatermark' => $generateWatermark
        ]);
    }

    protected function getDefaultMethods(): array
    {
        if ($this->design->hasText()) {
            return [
                static::METHOD_DUSK,
                static::METHOD_IMAGICK
            ];
        }

        if ($this->design->isPDF()) {
            return [
                static::METHOD_INKSCAPE,
                static::METHOD_DUSK,
                static::METHOD_IMAGICK
            ];
        }

        return [
            static::METHOD_IMAGICK,
            static::METHOD_DUSK
        ];
    }

    public function generate($method = null): bool
    {
        if (!$method) {
            $methods = $this->getDefaultMethods();
        } else {
            $methods = [$method];
        }

        $startTime = time();

        $this->design->setMetaData([
            'generation_server' => config('app.server_name'),
            'generation_start' => $startTime,
            'has_problem' => null
        ]);

        $this->design->update([
            'status' => Design::STATUS_PROCESSING,
            'file_name' => null
        ]);

        $success = false;
        foreach ($methods as $method) {
            $this->design->addLog("Generating Gang Sheet with method: {$method}");
            $this->design->setMetaData('generation_method', $method);

            $success = $this->generateByMethod($method);
            if ($success) {
                break;
            }
        }

        $timeConsumed = time() - $startTime;
        $this->design->setMetaData('generation_time', $timeConsumed);

        $completedTime = $this->design->getMetaData('completed_time');
        if (empty($completedTime)) {
            $orderedTime = $this->design->order->created_at->timestamp ?? null;
            if ($orderedTime) {
                $completedTime = time() - $orderedTime;
                $this->design->setMetaData('completed_time', $completedTime);
            }
        }

        if (!$this->design->gangSheetExists()) {
            $this->design->addLog("Gang sheet not found after generation");
            $success = false;
        }

        if ($success) {
            $this->design->addLog("Gang sheet generation completed in {$timeConsumed} seconds");
            $this->design->update(['status' => Design::STATUS_COMPLETED]);
            $this->design->uploadGangSheet();

            $this->design->sendWebhook('webhookGangSheetCompleted');

            if ($method === self::METHOD_INKSCAPE && ($this->design->hasMultiLineText() || $this->design->disableInkscape() || $this->design->hasFlippedImage())) {
                $this->design->setMetaData('has_problem', true);
            } else if ($method === self::METHOD_DUSK && $this->design->isPDF()) {
                $this->design->setMetaData('has_problem', true);
            } else if ($method === self::METHOD_IMAGICK && ($this->design->isPDF() || $this->design->hasText())) {
                $this->design->setMetaData('has_problem', true);
            }
        } else {
            $failedCount = $this->design->getMetaData('generation_failed_count', 0);
            $this->design->setMetaData('generation_failed_count', $failedCount + 1);

            $this->design->addLog("Gang sheet generation failed after {$timeConsumed} seconds");
            $this->design->update([
                'status' => Design::STATUS_FAILED
            ]);
        }

        return $success;
    }

    /**
     * @throws GenerationException
     */
    protected function createGenerator($method): static
    {
        $this->generator = match ($method) {
            static::METHOD_IMAGICK => new ImagickGenerator($this->options),
            static::METHOD_INKSCAPE => new InkscapeGenerator($this->options),
            static::METHOD_DUSK => new DuskGenerator($this->options),
        };

        $this->generator->setLogger($this->design);

        return $this;
    }

    protected function generateByMethod($method): bool
    {
        try {
            return $this->createGenerator($method)
                ->process();
        } catch (\Exception $exception) {
            $this->design->addLog("Failed to generate with {$method} on Error: ", $exception->getMessage());
            return false;
        }
    }

    static public function generateByImagick(string $design_id): void
    {
        self::create($design_id)->generate(static::METHOD_IMAGICK);
    }

    static public function generateByInkscape(string $design_id): void
    {
        self::create($design_id)->generate(static::METHOD_INKSCAPE);
    }

    static public function generateByDusk(string $design_id): void
    {
        self::create($design_id)->generate(static::METHOD_DUSK);
    }
}
