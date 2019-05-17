<?php

namespace App\GangSheet\Abstracts;

use App\GangSheet\Exceptions\GenerationException;

abstract class Generator
{
    protected string $design_id;

    protected int $width;

    protected int $height;

    protected array $objects;

    protected string $designUrl;

    protected int $actualHeight = 0;
    protected bool $autoTrimGangSheet = true;

    protected bool $printFileName = false;

    protected string $printFileNamePosition = 'top';

    protected float $fileNameHeight = 1;

    protected string $fileName = '';
    protected string $fileExtension = '.png';

    protected array $printQRLogo = [
        'enable' => false,
        'position' => 'inline',
        'type' => 'qr',
        'size' => 2,
        'qr' => 'Gang Sheet',
        'logo' => null,
    ];

    protected int $chromeDriverPortNumber = 9515;

    public string $content = '';

    protected bool $generateWatermark = false;

    protected bool $printPattern = false;

    public string $watermark = '';


    public string $thumbnail = '';

    protected $logger = null;

    protected string $fontPath;


    // Sticker Attributes
    protected int $stickerOutlineWidth = 1;

    protected string $stickerOutlineColor = '#000000';

    abstract protected function build(): bool;

    /**
     * @throws GenerationException
     */
    public function __construct(array $options = [])
    {
        if (!isset($options['design_id'])) {
            throw new GenerationException('Design ID is required');
        }

        if (!isset($options['width'])) {
            throw new GenerationException('Width is required');
        }

        if (!isset($options['height'])) {
            throw new GenerationException('Height is required');
        }

        if (!isset($options['objects'])) {
            throw new GenerationException('Objects are required');
        }

        if (!isset($options['designUrl'])) {
            throw new GenerationException('Design Url is required');
        }

        $this->design_id = $options['design_id'];

        $this->width = $options['width'];

        $this->height = $options['height'];

        $this->objects = $options['objects'];

        $this->designUrl = $options['designUrl'];

        if (isset($options['actualHeight'])) {
            $this->actualHeight = $options['actualHeight'];
        }

        if (isset($options['autoTrimGangSheet'])) {
            $this->autoTrimGangSheet = $options['autoTrimGangSheet'];
        }

        if (isset($options['printFileName'])) {
            $this->printFileName = $options['printFileName'];
        }

        if (isset($options['printFileNamePosition'])) {
            $this->printFileNamePosition = $options['printFileNamePosition'];
        }

        if (isset($options['fileNameHeight'])) {
            $this->fileNameHeight = $options['fileNameHeight'];
        }

        if (isset($options['fileName'])) {
            $this->fileName = $options['fileName'];
        }

        if (isset($options['printQRLogo'])) {
            $this->printQRLogo = $options['printQRLogo'];
        }

        if (isset($options['fileExtension'])) {
            $this->fileExtension = $options['fileExtension'];
        }

        if (isset($options['stickerOutlineWidth'])) {
            $this->stickerOutlineWidth = $options['stickerOutlineWidth'];
        }

        if (isset($options['stickerOutlineColor'])) {
            $this->stickerOutlineColor = $options['stickerOutlineColor'];
        }

        if (isset($options['generateWatermark'])) {
            $this->generateWatermark = $options['generateWatermark'];
        }

        if (isset($options['printPattern'])) {
            $this->printPattern = $options['printPattern'];
        }

        $this->fontPath = public_path('assets/fonts/Oswald/Oswald-Regular.ttf');
    }

    public function setLogger($logger): static
    {
        $this->logger = $logger;

        return $this;
    }

    protected function log(string $message, $data = null): void
    {
        $this->logger?->addLog($message, $data);
    }

    protected function getDesignWidth(): int
    {
        return $this->width;
    }

    protected function getDesignHeight(): int
    {
        return $this->height;
    }

    protected function getActualHeight(): int
    {
        if ($this->autoTrimGangSheet && $this->actualHeight) {
            return $this->actualHeight;
        }

        return $this->getDesignHeight();
    }

    protected function waitForFile($filePath, $timeout = 120): bool
    {
        $start = time();
        while (!file_exists($filePath)) {
            if (time() - $start > $timeout) {
                return false;
            }
            sleep(1);
        }

        return true;
    }

    protected function isPDF(): bool
    {
        return $this->fileExtension === '.pdf';
    }

    protected function isPNG(): bool
    {
        return $this->fileExtension === '.png';
    }

    protected function isEPS(): bool
    {
        return $this->fileExtension === '.eps';
    }

    protected function isTIFF(): bool
    {
        return $this->fileExtension === '.tiff';
    }
}
