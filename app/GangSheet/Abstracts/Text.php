<?php

namespace App\GangSheet\Abstracts;

use Imagick;
use ImagickDraw;

abstract class Text extends Element
{
    public string $type = 'text';

    protected string $text = '';

    protected string $fontFamily = 'Arial';

    protected int $fontSize = 40;
    protected string $fillColor = '#000000';
    protected string $strokeColor = 'transparent';
    protected int $strokeWidth = 0;

    protected string $backgroundColor = 'transparent';
    protected string $fontStyle = 'normal';
    protected string $fontWeight = 'normal';

    protected bool $underline = false;
    protected string $textAlign = 'left';
    protected ?string $textAnchor = null;
    protected array $baseLines = [];
    protected array $leftLines = [];
    protected array $textLines = [];
    protected bool $flipX = false;
    protected bool $flipY = false;

    protected ?Imagick $imagick = null;
    protected ?ImagickDraw $draw = null;

    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->textLines = preg_split('/\r?\n/', $this->text);
    }

    public function isMultiline(): bool
    {
        return count($this->textLines) > 1;
    }
}
