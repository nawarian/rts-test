<?php

declare(strict_types=1);

namespace RTS\Grid;

use ArrayAccess;
use Iterator;
use SplFixedArray;
use Traversable;

final class Grid2D implements Traversable, Iterator, ArrayAccess
{
    use Grid2DIteratorTrait;
    use Grid2DArrayAccessTrait;

    private SplFixedArray $cells;

    public function __construct(int $cols, int $rows)
    {
        $this->cells = new SplFixedArray($cols * $rows);

        $c = 0;
        for ($i = 0; $i < $rows; ++$i) {
           for ($j = 0; $j < $cols; ++$j) {
               $this->cells[$c++] = new Cell($j, $i);
           }
        }
    }
}
