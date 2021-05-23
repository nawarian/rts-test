<?php

declare(strict_types=1);

namespace RTS\Objects;

use Nawarian\Raylib\Raylib;
use Nawarian\Raylib\Types\Color;
use Nawarian\Raylib\Types\Rectangle;
use Nawarian\Raylib\Types\Vector2;
use RTS\GameState;
use RTS\Grid\Cell;
use SplObjectStorage;
use SplPriorityQueue;
use function RTS\manhattanDistance;

class Villager extends Unit
{
    private const WIDTH = 128;
    private const HEIGHT = 128;

    private Rectangle $shape;
    private ?Cell $goal = null;
    private float $walkStepsInterval = 0.3; // time consumed per step (seconds)
    private float $lastStep = 0.0;

    private SplPriorityQueue $waypoints;

    public function __construct(Vector2 $pos)
    {
        parent::__construct($pos);
        $this->shape = new Rectangle(0, 0, self::WIDTH, self::HEIGHT);
        $this->waypoints = new SplPriorityQueue();
    }

    public function update(): void
    {
        $currentCell = GameState::$grid->cell((int) $this->pos->x, (int) $this->pos->y);
        if (GameState::$raylib->isMouseButtonPressed(Raylib::MOUSE_LEFT_BUTTON)) {
            $clickedCoords = GameState::$raylib->getScreenToWorld2D(
                GameState::$raylib->getMousePosition(),
                GameState::$camera,
            );

            if (GameState::$raylib->checkCollisionPointRec($clickedCoords, $currentCell->rec)) {
                $this->select();
            } elseif ($this->isSelected()) {
                $this->deselect();
            }
        }

        // Set waypoints
        if ($this->isSelected() && GameState::$raylib->isMouseButtonPressed(Raylib::MOUSE_RIGHT_BUTTON)) {
            $clickedCoords = GameState::$raylib->getScreenToWorld2D(
                GameState::$raylib->getMousePosition(),
                GameState::$camera,
            );
            $goal = GameState::$grid->cellByWorldCoords((int) $clickedCoords->x, (int) $clickedCoords->y);
            $this->goal = $goal;

            $this->setWaypointsTowards($goal);
        }

        // Consuming waypoints (movement)
        $delta = GameState::$raylib->getTime() - $this->lastStep;
        if ($delta >= $this->walkStepsInterval && !$this->waypoints->isEmpty()) {
            $this->lastStep = GameState::$raylib->getTime();

            $nextStep = $this->waypoints->extract();
            if ($nextStep->x === $this->pos->x && $nextStep->y == $this->pos->y) {
                return;
            }

            $nextCell = GameState::$grid->cell((int) $nextStep->x, (int) $nextStep->y);

            $nextCellBlocked = $nextCell->data['collides'] ?? false;
            // If next step is blocked, recalculate route
            if ($nextCellBlocked && ($this->goal ?? false)) {
                $this->setWaypointsTowards($this->goal);
            } elseif ($nextCellBlocked) {
                // If next step is blocked and we don't have a goal for some reason, stop
                $this->setWaypointsTowards($currentCell);
            } else {
                // Path is unblocked, let's just update the Unit's position
                $currentCell->unit = null;
                $currentCell->data['collides'] = false;
                $this->pos = $nextCell->pos;
                $nextCell->unit = $this;
                $nextCell->data['collides'] = true;
            }
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

        $current = GameState::$grid->cell((int)$this->pos->x, (int)$this->pos->y);
        $frontier->insert($current, 0);
        $costSoFar[$current] = 0;

        while (!$frontier->isEmpty()) {
            $current = $frontier->extract();

            if ($current === $goal) {
                break;
            }

            /** @var Cell $neighbour */
            foreach (GameState::$grid->neighbours($current) as $next) {
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
        $cell = GameState::$grid->cell((int) $this->pos->x, (int) $this->pos->y);

        $rec->x = $cell->rec->x;
        $rec->y = $cell->rec->y;

        GameState::$tileset->get(120)->draw($rec, 0, 1, Color::white());
        if ($this->isSelected()) {
            GameState::$tileset->get(120)->draw($rec, 0, 1, Color::red(50));
        }
    }
}
