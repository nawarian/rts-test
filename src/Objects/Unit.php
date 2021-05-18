<?php

declare(strict_types=1);

namespace RTS\Objects;

use Nawarian\Raylib\Types\Vector2;
use RTS\GameState;

abstract class Unit
{
    protected GameState $state;
    public Vector2 $pos;
    private bool $selected = false;

    public function __construct(GameState $state, Vector2 $pos)
    {
        $this->state = $state;
        $this->pos = $pos;
    }

    public function select(): void
    {
        $this->selected = true;
    }

    public function deselect(): void
    {
        $this->selected = false;
    }

    public function isSelected(): bool
    {
        return $this->selected;
    }

    public abstract function update(): void;
    public abstract function draw(): void;
}
