<?php

namespace App\GangSheet\Abstracts;

use App\Extensions\Image\Image;

abstract class Element
{
    public string $type;

    public float $x = 0;

    public float $offsetX = 0;

    public float $y = 0;

    public float $offsetY = 0;

    public float $width = 0;

    public float $height = 0;

    public float $centerX = 0;

    public float $centerY = 0;

    public float $left = 0;

    public float $top = 0;

    public float $right = 0;

    public float $bottom = 0;

    public float $scaleX = 1;

    public float $scaleY = 1;

    public float $angle = 0;

    public array $rect = [];

    public int $stickerOutlineWidth = 1;

    public function __construct($options = [])
    {
        foreach ($options as $key => $value) {
            if (!is_null($value)) {
                $this->{$key} = $value;
            }
        }

        $this->centerX = $this->x - $this->offsetX;
        $this->centerY = $this->y - $this->offsetY;

        $this->left = $this->centerX - $this->width / 2;
        $this->top = $this->centerY - $this->height / 2;
        $this->right = $this->centerX + $this->width / 2;
        $this->bottom = $this->centerY + $this->height / 2;
    }

    public static function create($options = []): static
    {
        return new static($options);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
