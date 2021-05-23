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

    public SplFixedArray $cells;
    private int $rows;
    private int $cols;
    private int $colSize;
    private int $rowSize;

    public function __construct(int $cols, int $rows, int $colSize = 128, int $rowSize = 128)
    {
        $this->cells = new SplFixedArray($cols * $rows);
        $this->cols = $cols;
        $this->colSize = $colSize;
        $this->rows = $rows;
        $this->rowSize = $rowSize;

        $c = 0;
        for ($y = 0; $y < $rows; ++$y) {
           for ($x = 0; $x < $cols; ++$x) {
               $this->cells[$c++] = new Cell(
                   new Vector2($x, $y),
                   new Rectangle($x * $colSize, $y * $rowSize, $colSize, $rowSize),
               );
           }
        }
    }

    public function cellByWorldCoords(int $x, int $y): Cell
    {
        return $this->cell((int) ($x / $this->colSize), (int) ($y / $this->rowSize));
    }

    public function cell(int $x, int $y): Cell
    {
        return $this->cells[$this->indexOf($x, $y)];
    }

    public function indexOf(int $x, int $y): int
    {
        return (int) min($x + ($y * $this->cols), count($this->cells) - 1);
    }

    public function indexOfWorldCoords(int $x, int $y): int
    {
        return $this->indexOf((int) ($x / $this->colSize), (int) ($y / $this->rowSize));
    }

    public function neighbours(Cell $cell): iterable
    {
        if ($cell->pos->x > 0) {
            yield $this->cell((int) $cell->pos->x - 1, (int) $cell->pos->y);

            if ($cell->pos->y > 0) {
                yield $this->cell((int) $cell->pos->x - 1, (int) $cell->pos->y - 1);
            }

            if ($cell->pos->y < $this->rows - 1) {
                yield $this->cell((int) $cell->pos->x - 1, (int) $cell->pos->y + 1);
            }
        }

        if ($cell->pos->x < $this->cols - 1) {
            yield $this->cell((int) $cell->pos->x + 1, (int) $cell->pos->y);

            if ($cell->pos->y > 0) {
                yield $this->cell((int) $cell->pos->x + 1, (int) $cell->pos->y - 1);
            }

            if ($cell->pos->y < $this->rows - 1) {
                yield $this->cell((int) $cell->pos->x + 1, (int) $cell->pos->y + 1);
            }
        }

        if ($cell->pos->y > 0) {
            yield $this->cell((int) $cell->pos->x, (int) $cell->pos->y - 1);
        }

        if ($cell->pos->y < $this->rows - 1) {
            yield $this->cell((int) $cell->pos->x, (int) $cell->pos->y + 1);
        }
    }
}
