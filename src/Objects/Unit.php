<?php

declare(strict_types=1);

namespace RTS\Objects;

use Nawarian\Raylib\Raylib;
use Nawarian\Raylib\Types\Vector2;
use RTS\Grid\Grid2D;

abstract class Unit
{
    protected Raylib $raylib;
    protected Grid2D $grid;
    protected Vector2 $pos;

    public function __construct(Raylib $raylib, Grid2D $grid, Vector2 $pos)
    {
        $this->raylib = $raylib;
        $this->grid = $grid;
        $this->pos = $pos;
    }

    public abstract function update(): void;
    public abstract function draw(): void;
}
