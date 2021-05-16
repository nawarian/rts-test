<?php

declare(strict_types=1);

namespace RTS\Grid;

use ArrayAccess;
use Iterator;
use Nawarian\Raylib\Types\Rectangle;
use Nawarian\Raylib\Types\Vector2;
use SplFixedArray;
use Traversable;

final class Grid2D implements Traversable, Iterator, ArrayAccess
{
    use Grid2DIteratorTrait;
    use Grid2DArrayAccessTrait;

    private SplFixedArray $cells;
    private int $rows;
    private int $cols;

    public function __construct(int $cols, int $rows, int $colSize = 128, int $rowSize = 128)
    {
        $this->cells = new SplFixedArray($cols * $rows);
        $this->cols = $cols;
        $this->rows = $rows;

        $c = 0;
        for ($i = 0; $i < $rows; ++$i) {
           for ($j = 0; $j < $cols; ++$j) {
               $this->cells[$c++] = new Cell(
                   new Vector2($j, $i),
                   new Rectangle($j * $colSize, $i * $rowSize, $colSize, $rowSize),
               );
           }
        }
    }

    public function cellByWorldCoords(int $x, int $y): Cell
    {
        foreach ($this->cells as $cell) {
            if ($x >= $cell->rec->x && $x <= ($cell->rec->x + $cell->rec->width)) {
                if ($y >= $cell->rec->y && $y <= ($cell->rec->y + $cell->rec->height)) {
                    break;
                }
            }
        }

        return $cell;
    }

    public function cell(int $x, int $y): Cell
    {
        $c = 0;
        for ($i = 0; $i < $this->rows; ++$i) {
            for ($j = 0; $j < $this->cols; ++$j) {
                if ($i === $y && $j === $x) {
                    break(2);
                }
                $c++;
            }
        }

        return $this->cells[$c];
    }

    public function neighbours(Cell $cell): iterable
    {
        $cells = [];
        if ($cell->pos->x > 0) {
            $cells[] = $this->cell((int) $cell->pos->x - 1, (int) $cell->pos->y);
        }

        if ($cell->pos->x < $this->cols - 1) {
            $cells[] = $this->cell((int) $cell->pos->x + 1, (int) $cell->pos->y);
        }

        if ($cell->pos->y > 0) {
            $cells[] = $this->cell((int) $cell->pos->x, (int) $cell->pos->y - 1);
        }

        if ($cell->pos->y < $this->rows - 1) {
            $cells[] = $this->cell((int) $cell->pos->x, (int) $cell->pos->y + 1);
        }

        return $cells;
    }
}
