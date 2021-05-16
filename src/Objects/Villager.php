<?php

declare(strict_types=1);

namespace RTS\Objects;

use Nawarian\Raylib\Raylib;
use Nawarian\Raylib\Types\Rectangle;
use Nawarian\Raylib\Types\Vector2;
use RTS\GameState;
use RTS\Grid\Cell;
use RTS\Spritesheet;
use SplObjectStorage;
use SplPriorityQueue;
use function RTS\manhattanDistance;

class Villager extends Unit
{
    private const WIDTH = 128;
    private const HEIGHT = 128;

    private Rectangle $shape;
    private Spritesheet $spritesheet;
    private float $walkStepsInterval = 0.3; // time consumed per step (seconds)
    private float $lastStep = 0.0;

    private SplPriorityQueue $waypoints;

    public function __construct(GameState $state, Vector2 $pos, Spritesheet $spritesheet)
    {
        parent::__construct($state, $pos);
        $this->shape = new Rectangle(0, 0, self::WIDTH, self::HEIGHT);
        $this->spritesheet = $spritesheet;
        $this->waypoints = new SplPriorityQueue();
    }

    public function update(): void
    {
        // Set waypoints
        if ($this->state->raylib->isMouseButtonPressed(Raylib::MOUSE_RIGHT_BUTTON)) {
            $dest = $this->state->raylib->getScreenToWorld2D(
                $this->state->raylib->getMousePosition(),
                $this->state->camera,
            );
            $goal = $this->state->grid->cellByWorldCoords((int) $dest->x, (int) $dest->y);

            $this->setWaypointsTowards($goal);
        }

        // Consuming waypoints (movement)
        $delta = $this->state->raylib->getTime() - $this->lastStep;
        if ($delta >= $this->walkStepsInterval && !$this->waypoints->isEmpty()) {
            $this->lastStep = $this->state->raylib->getTime();

            $waypoint = $this->waypoints->extract();
            $this->pos = $waypoint;
        }
    }

    private function setWaypointsTowards(Cell $goal): void
    {
        if ($goal->data['collides'] ?? false) {
            // @todo fetch closest node instead of skipping buildings
            return;
        }

        // Clear any previous waypoints (interrupts previous movement if any)
        $this->waypoints = new SplPriorityQueue();

        $frontier = new SplPriorityQueue();
        $cameFrom = new SplObjectStorage();
        $costSoFar = new SplObjectStorage();

        $current = $this->state->grid->cell((int)$this->pos->x, (int)$this->pos->y);
        $frontier->insert($current, 0);
        $costSoFar[$current] = 0;

        while (!$frontier->isEmpty()) {
            $current = $frontier->extract();

            if ($current === $goal) {
                break;
            }

            /** @var Cell $neighbour */
            foreach ($this->state->grid->neighbours($current) as $next) {
                if ($next->data['collides'] ?? false) {
                    continue;
                }

                $gCost = manhattanDistance($current->pos, $next->pos);
                $newCost = $costSoFar[$current] + $gCost;

                if (!$costSoFar->contains($next) || $newCost < $costSoFar[$next]) {
                    $costSoFar[$next] = $newCost;
                    $hCost = manhattanDistance($goal->pos, $next->pos);
                    // f(n) = g(n) + h(n) (see https://www.redblobgames.com/pathfinding/a-star/introduction.html)
                    $priority = $newCost + $hCost;

                    // Stores with 1/$priority because we need the smallest first
                    $frontier->insert($next, 1 / $priority);

                    $cameFrom[$next] = $current;
                }
            }
        }

        $i = 0;
        $last = $goal;
        $this->waypoints->insert($last->pos, $i++);
        while ($cameFrom->contains($last)) {
            $last = $cameFrom[$last];
            $this->waypoints->insert($last->pos, $i++);
        }
    }

    public function draw(): void
    {
        $rec = clone $this->shape;
        $cell = $this->state->grid->cell((int) $this->pos->x, (int) $this->pos->y);

        $rec->x = $cell->rec->x;
        $rec->y = $cell->rec->y;

        $this->spritesheet->get(120)->draw($rec, 0, 1);
    }
}
