<?php

declare(strict_types=1);

namespace RTS\Objects;

use Nawarian\Raylib\Raylib;
use Nawarian\Raylib\Types\Camera2D;
use Nawarian\Raylib\Types\Color;
use Nawarian\Raylib\Types\Rectangle;
use Nawarian\Raylib\Types\Vector2;
use RTS\Grid\Cell;
use RTS\Grid\Grid2D;

class Villager extends Unit
{
    private const WIDTH = 128;
    private const HEIGHT = 128;

    private Camera2D $camera;
    private Rectangle $shape;
    private float $walkSpeed = 0.7; // steps per second
    private float $lastStep = 0.0;

    private array $waypoints = [];

    public function __construct(Raylib $raylib, Camera2D $camera, Grid2D $grid, Vector2 $pos)
    {
        parent::__construct($raylib, $grid, $pos);

        $this->camera = $camera;
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
        $this->waypoints = [];

        $dest = $this->raylib->getScreenToWorld2D($dest, $this->camera);
        $cell = $this->grid->cellByWorldCoords((int) $dest->x, (int) $dest->y);
        $dest = $cell->pos;

        $start = $this->grid->cell((int) $this->pos->x, (int) $this->pos->y);
        while (true) {
            if ($start->pos->x === $dest->x && $start->pos->y === $dest->y) {
                break;
            }

            /** @var Cell[] $neighbours */
            $neighbours = $this->grid->neighbours($start);

            $h = [];
            foreach ($neighbours as $neighbour) {
                $heuristic = abs($neighbour->pos->x - $dest->x) + abs($neighbour->pos->y - $dest->y);
                $h[$heuristic] = $neighbour;
            }
            ksort($h);

            $start = array_shift($h);
            $this->waypoints[] = $start->pos;
        }
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
