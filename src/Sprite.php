<?php

declare(strict_types=1);

namespace RTS;

use Nawarian\Raylib\Raylib;
use Nawarian\Raylib\Types\Color;
use Nawarian\Raylib\Types\Rectangle;
use Nawarian\Raylib\Types\Texture2D;
use Nawarian\Raylib\Types\Vector2;

final class Sprite
{
    private Raylib $raylib;
    private Texture2D $tex;
    private Rectangle $source;

    public function __construct(Raylib $raylib, Texture2D $tex, Rectangle $source)
    {
        $this->raylib = $raylib;
        $this->tex = $tex;
        $this->source = $source;
    }

    public function draw(Rectangle $dest, float $rotation, float $scale): void
    {
        $this->raylib->drawTextureTiled(
            $this->tex,
            $this->source,
            $dest,
            new Vector2(0, 0),
            $rotation,
            $scale,
            Color::white(),
        );
    }
}
