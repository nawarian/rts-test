<?php

declare(strict_types=1);

namespace RTS\Grid;

final class Cell
{
    public int $x = 0;
    public int $y = 0;
    public array $data = [];

    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
    }
}
