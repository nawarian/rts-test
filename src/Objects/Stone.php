<?php

declare(strict_types=1);

namespace RTS\Objects;

use Nawarian\Raylib\Types\Rectangle;
use Nawarian\Raylib\Types\Vector2;
use RTS\GameState;

final class Stone extends Unit
{
    private const WIDTH = 128;
    private const HEIGHT = 128;

    private Rectangle $shape;

    public function __construct(Vector2 $pos)
    {
        parent::__construct($pos);
        $this->shape = new Rectangle(0, 0, self::WIDTH, self::HEIGHT);
    }

    public function update(): void
    {
    }

    public function draw(): void
    {
        $rec = clone $this->shape;
        $cell = GameState::$grid->cell((int) $this->pos->x, (int) $this->pos->y);

        $rec->x = $cell->rec->x;
        $rec->y = $cell->rec->y;

        GameState::$tileset->get(80)->draw($rec, 0, 1);
    }
}
