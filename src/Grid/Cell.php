<?php

declare(strict_types=1);

namespace RTS\Grid;

use Nawarian\Raylib\Types\Rectangle;

final class Cell
{
    public Rectangle $rec;
    public array $data = [];

    public function __construct(Rectangle $rec)
    {
        $this->rec = $rec;
    }
}
