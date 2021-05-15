<?php

declare(strict_types=1);

namespace RTS;

use Nawarian\Raylib\Raylib;
use Nawarian\Raylib\Types\Camera2D;
use RTS\Grid\Cell;
use RTS\Grid\Grid2D;
use RTS\Objects\Unit;

final class GameState
{
    public Raylib $raylib;
    public Grid2D $grid;
    public Camera2D $camera;

    public function __construct(Raylib $raylib, Grid2D $grid, Camera2D $camera)
    {
        $this->raylib = $raylib;
        $this->grid = $grid;
        $this->camera = $camera;
    }

    public function update(): void
    {
        foreach ($this->grid as $cell) {
            /** @var Unit $unit */
            foreach ($cell->data['units'] ?? [] as $unit) {
                $unit->update();
            }
        }
    }

    public function add(Unit $unit): self
    {
        $cell = $this->cell((int) $unit->pos->x, (int) $unit->pos->y);
        $cell->data['units'] = $cell->data['units'] ?? [];
        $cell->data['units'][] = $unit;

        return $this;
    }

    public function cell(int $x, int $y): Cell
    {
        return $this->grid->cell($x, $y);
    }
}
