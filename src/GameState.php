<?php

declare(strict_types=1);

namespace RTS;

use Nawarian\Raylib\Raylib;
use Nawarian\Raylib\Types\Camera2D;
use RTS\Grid\Grid2D;
use RTS\Objects\Unit;

final class GameState
{
    public Raylib $raylib;
    public Grid2D $grid;
    public Camera2D $camera;
    public bool $debug = true;

    public function __construct(Raylib $raylib, Grid2D $grid, Camera2D $camera)
    {
        $this->raylib = $raylib;
        $this->grid = $grid;
        $this->camera = $camera;
    }

    public function update(): void
    {
        if ($this->raylib->isKeyPressed(Raylib::KEY_TAB)) {
            $this->debug = !$this->debug;
        }

        foreach ($this->grid as $cell) {
            $cell->data['collides'] = !is_null($cell->unit);
            $cell->unit && $cell->unit->update();
        }
    }

    public function add(Unit $unit): self
    {
        $cell = $this->grid->cell((int) $unit->pos->x, (int) $unit->pos->y);
        $cell->unit = $unit;

        return $this;
    }
}
