<?php

declare(strict_types=1);

namespace RTS\Objects;

use Nawarian\Raylib\Raylib;
use Nawarian\Raylib\Types\Color;
use Nawarian\Raylib\Types\Rectangle;
use Nawarian\Raylib\Types\Vector2;
use RTS\GameState;
use RTS\Grid\Cell;
use RTS\Spritesheet;
use SplPriorityQueue;

class Villager extends Unit
{
    private const WIDTH = 128;
    private const HEIGHT = 128;

    private Rectangle $shape;
    private Spritesheet $spritesheet;
    private float $walkSpeed = 0.7; // steps per second
    private float $lastStep = 0.0;

    private array $debug = [];

    private array $waypoints = [];

    public function __construct(GameState $state, Vector2 $pos, Spritesheet $spritesheet)
    {
        parent::__construct($state, $pos);
        $this->shape = new Rectangle(0, 0, self::WIDTH, self::HEIGHT);
        $this->spritesheet = $spritesheet;
    }

    public function update(): void
    {
        if ($this->state->raylib->isMouseButtonPressed(Raylib::MOUSE_RIGHT_BUTTON)) {
            $this->moveTo($this->state->raylib->getMousePosition());
        }

        $delta = $this->state->raylib->getTime() - $this->lastStep;
        if ($delta >= $this->walkSpeed && count($this->waypoints) > 0) {
            $this->step();
        }
    }

    private function moveTo(Vector2 $dest): void
    {
        $this->waypoints = [];

        $dest = $this->state->raylib->getScreenToWorld2D($dest, $this->state->camera);
        $goal = $this->state->grid->cellByWorldCoords((int) $dest->x, (int) $dest->y);

        if ($this->state->debug) {
            $this->debug['waypoints'] = [];
            $this->debug['goal'] = $goal;
        }

        $current = $this->state->grid->cell((int)$this->pos->x, (int)$this->pos->y);
        while (true) {
            if ($current === $goal) {
                break;
            }

            $q = new SplPriorityQueue();
            $neighbours = $this->state->grid->neighbours($current);
            foreach ($neighbours as $next) {
                $cost = $this->heuristic($goal->pos, $next->pos);
                // Invert `$cost` to we reverse the priority queue's implementation
                $q->insert($next, 1 / $cost);
            }

            $current = $q->top();
            $this->waypoints[] = $current->pos;

            if ($this->state->debug) {
                $this->debug['waypoints'][] = $current;
            }
        }
    }

    private function cost(Cell $current, Cell $next): int
    {
        if ($next->data['collides'] ?? false) {
            return 10;
        }

        return 1;
    }

    private function heuristic(Vector2 $node, Vector2 $goal): int
    {
        $dx = abs($goal->x - $node->x);
        $dy = abs($goal->y - $node->y);

        $heuristic = (int) (1 * ($dx + $dy));
        return $heuristic + 1;
    }

    public function step(): void
    {
        $this->lastStep = $this->state->raylib->getTime();

        $waypoint = array_shift($this->waypoints);
        $this->pos = $waypoint;

        if ($this->state->debug && count($this->waypoints) === 0) {
            $this->debug['waypoints'] = [];
            $this->debug['goal'] = null;
        }
    }

    public function draw(): void
    {
        $rec = clone $this->shape;
        $cell = $this->state->grid->cell((int) $this->pos->x, (int) $this->pos->y);

        $rec->x = $cell->rec->x;
        $rec->y = $cell->rec->y;

        $this->spritesheet->get(120)->draw($rec, 0, 1);

        if ($this->state->debug) {
            $this->drawDebug();
        }
    }

    private function drawDebug(): void
    {
        $waypoints = $this->debug['waypoints'] ?? [];
        $green = Color::gold();
        $green->alpha = 100;

        /** @var Cell $waypoint */
        foreach ($waypoints as $waypoint) {
            $this->state->raylib->drawRectangleRec($waypoint->rec, $green);
        }

        /** @var Cell|null $goal */
        if ($goal = $this->debug['goal'] ?? null) {
            $blue = Color::blue();
            $blue->alpha = 120;
            $this->state->raylib->drawRectangleRec($goal->rec, $blue);
        }
    }
}
