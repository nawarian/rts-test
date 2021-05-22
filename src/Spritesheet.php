<?php

declare(strict_types=1);

namespace RTS;

use Nawarian\Raylib\Types\Rectangle;
use Nawarian\Raylib\Types\Texture2D;

final class Spritesheet
{
    private Texture2D $tex;

    private int $margin;
    private int $spacing;
    private int $spriteWidth;
    private int $spriteHeight;

    public function __construct(
        Texture2D $texture,
        int $margin,
        int $spacing,
        int $spriteWidth,
        int $spriteHeight
    ) {
        $this->tex = $texture;

        $this->margin = $margin;
        $this->spacing = $spacing;
        $this->spriteWidth = $spriteWidth;
        $this->spriteHeight = $spriteHeight;
    }

    public function get(int $gid): Sprite
    {
        $rec = new Rectangle($this->margin, $this->margin, $this->spriteWidth, $this->spriteHeight);
        $cols = (int) ($this->tex->width / ($this->spriteWidth + $this->spacing));

        $row = (int) ($gid / $cols);
        $col = (int) ($gid - ($row * $cols)) - 1;

        $rec->x += $this->spriteWidth * $col + ($this->spacing * $col);
        $rec->y += $this->spriteHeight * $row + ($this->spacing * $row);

        return new Sprite($this->tex, $rec);
    }
}
