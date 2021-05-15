<?php

declare(strict_types=1);

namespace RTS\Objects;

use Nawarian\Raylib\Types\Vector2;
use RTS\GameState;

abstract class Unit
{
    protected GameState $state;
    public Vector2 $pos;

    public function __construct(GameState $state, Vector2 $pos)
    {
        $this->state = $state;
        $this->pos = $pos;
    }

    public abstract function update(): void;
    public abstract function draw(): void;
}
