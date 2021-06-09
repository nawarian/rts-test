<?php

declare(strict_types=1);

namespace RTS\Objects;

use Nawarian\Raylib\Types\Rectangle;
use Nawarian\Raylib\Types\Vector2;

abstract class Unit
{
    public Vector2 $pos;
    public Rectangle $collision;
    private bool $selected = false;

    public function __construct(Vector2 $pos, Rectangle $collision)
    {
        $this->pos = $pos;
        $this->collision = $collision;
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
