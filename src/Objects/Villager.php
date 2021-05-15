<?php

declare(strict_types=1);

namespace RTS\Objects;

use Nawarian\Raylib\Raylib;
use Nawarian\Raylib\Types\Color;
use Nawarian\Raylib\Types\Rectangle;
use Nawarian\Raylib\Types\Vector2;
use RTS\Grid\Grid2D;

class Villager extends Unit
{
    private const WIDTH = 128;
    private const HEIGHT = 128;

    private Rectangle $shape;
    private float $walkSpeed = 0.7; // steps per second
    private float $lastStep = 0.0;

    private array $waypoints = [];

    public function __construct(Raylib $raylib, Grid2D $grid, Vector2 $pos)
    {
        parent::__construct($raylib, $grid, $pos);

        $this->shape = new Rectangle(0, 0, self::WIDTH, self::HEIGHT);
    }

    public function update(): void
    {
        if ($this->raylib->isMouseButtonPressed(Raylib::MOUSE_RIGHT_BUTTON)) {
            $this->moveTo($this->raylib->getMousePosition());
        }
        $delta = $this->raylib->getTime() - $this->lastStep;
        if ($delta >= $this->walkSpeed) {
            $this->step();
        }
    }

    private function moveTo(Vector2 $dest): void
    {
        // @todo -> set waypoints
    }

    public function step(): void
    {
        $this->lastStep = $this->raylib->getTime();

        $waypoint = array_shift($this->waypoints);
        if ($waypoint) {
            $this->pos = $waypoint;
        }
    }

    public function draw(): void
    {
        $rec = clone $this->shape;
        $cell = $this->grid->cell((int) $this->pos->x, (int) $this->pos->y);

        $rec->x = $cell->rec->x;
        $rec->y = $cell->rec->y;

        $this->raylib->drawRectangleRec($rec, Color::red());
    }
}
