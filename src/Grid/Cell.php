<?php

declare(strict_types=1);

namespace RTS\Grid;

use Nawarian\Raylib\Types\Rectangle;
use Nawarian\Raylib\Types\Vector2;

final class Cell
{
    public Vector2 $pos;
    public Rectangle $rec;
    public array $data = [];

    public function __construct(Vector2 $pos, Rectangle $rec)
    {
        $this->pos = $pos;
        $this->rec = $rec;
    }
}
