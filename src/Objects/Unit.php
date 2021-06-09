<?php

declare(strict_types=1);

namespace RTS\Objects;

use Nawarian\Raylib\Types\Rectangle;
use Nawarian\Raylib\Types\Vector2;
use RTS\Sprite;

abstract class Unit
{
    public Vector2 $pos;
    public Rectangle $collision;
    public Sprite $sprite;
    private bool $selected = false;

    public function __construct(Vector2 $pos, Rectangle $collision, Sprite $sprite)
    {
        $this->pos = $pos;
        $this->collision = $collision;
        $this->sprite = $sprite;
    }

    public function select(): void
    {
        $this->selected = true;
    }

    public function deselect(): void
    {
        $this->selected = false;
    }

    public function isSelected(): bool
    {
        return $this->selected;
    }

    public abstract function update(): void;
    public abstract function draw(): void;
}
