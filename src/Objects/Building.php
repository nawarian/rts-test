<?php

declare(strict_types=1);

namespace RTS\Objects;

use Nawarian\Raylib\Types\Rectangle;
use Nawarian\Raylib\Types\Vector2;
use RTS\GameState;
use RTS\Spritesheet;

class Building extends Unit
{
    private const WIDTH = 128;
    private const HEIGHT = 128;

    private Rectangle $shape;
    private Spritesheet $spritesheet;

    public function __construct(GameState $state, Vector2 $pos, Spritesheet $spritesheet)
    {
        parent::__construct($state, $pos);
        $this->shape = new Rectangle(0, 0, self::WIDTH, self::HEIGHT);
        $this->spritesheet = $spritesheet;
    }

    public function update(): void
    {
    }

    public function draw(): void
    {
        $rec = clone $this->shape;
        $cell = $this->state->grid->cell((int) $this->pos->x, (int) $this->pos->y);

        $rec->x = $cell->rec->x;
        $rec->y = $cell->rec->y;

        $this->spritesheet->get(118)->draw($rec, 0, 1);
    }
}