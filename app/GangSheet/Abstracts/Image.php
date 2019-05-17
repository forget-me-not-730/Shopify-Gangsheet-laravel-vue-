<?php

namespace App\GangSheet\Abstracts;

abstract class Image extends Element
{
    public string $src = '';

    public string $cachePath = '';

    public bool $flipX = false;

    public bool $flipY = false;

    public array $filters = [];

    public float|null $opacity = null;

    abstract public function getSourceId(): string;

    public function __construct($options = [])
    {
        parent::__construct($options);

        $baseName = basename($this->src);
        $fileName = explode('?', $baseName)[0];

        if (str_ends_with($fileName, '.svg')) {
            $this->type = 'svg';
        } else {
            $this->type = 'png';
        }
    }

    public function isSvg(): bool
    {
        return $this->type === 'svg';
    }

    public function isPng(): bool
    {
        return $this->type === 'png';
    }

    public function getTransforms()
    {
        $transforms = [];

        if ($this->flipX) {
            $transforms['flipX'] = true;
        }

        if ($this->flipY) {
            $transforms['flipY'] = true;
        }

        if ($this->angle) {
            $transforms['angle'] = $this->angle;
        }

        if (count($this->filters) > 0) {
            $transforms['filters'] = $this->filters;
        }

        return $transforms;
    }

    public function getSrc(): string
    {
        if ($this->cachePath && file_exists($this->cachePath)) {
            return $this->cachePath;
        }

        return $this->src;
    }

    public function cache($cacheDirectory): void
    {
        if ($this->isPng()) {
            $this->cachePath = $cacheDirectory . '/' . $this->getSourceId() . '.png';
        } else {
            $this->cachePath = $cacheDirectory . '/' . $this->getSourceId() . '.svg';
        }

        if (!file_exists($this->cachePath)) {
            $image = file_get_contents(encode_url($this->src));
            file_put_contents($this->cachePath, $image);
        }
    }
}
