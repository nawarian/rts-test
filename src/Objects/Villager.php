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
    private Vector2 $shapeTranslation;
    private SplPriorityQueue $waypoints;

    public function __construct(Vector2 $pos)
    {
        parent::__construct($pos);
        $this->shape = new Rectangle(0, 0, self::WIDTH, self::HEIGHT);
        $this->shapeTranslation = new Vector2(0, 0);
        $this->waypoints = new SplPriorityQueue();
    }

    public function update(): void
    {
        $currentCell = GameState::$grid->cell((int) $this->pos->x, (int) $this->pos->y);

        $this->toggleSelection($currentCell);
        $this->updateWaypoints();
        $this->walk($currentCell);
    }

    private function toggleSelection(Cell $currentCell): void
    {
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
    }

    private function updateWaypoints(): void
    {
        if (!$this->isSelected() || !GameState::$raylib->isMouseButtonPressed(Raylib::MOUSE_RIGHT_BUTTON)) {
            return;
        }

        $clickedCoords = GameState::$raylib->getScreenToWorld2D(
            GameState::$raylib->getMousePosition(),
            GameState::$camera,
        );
        $goal = GameState::$grid->cellByWorldCoords((int) $clickedCoords->x, (int) $clickedCoords->y);
        if ($goal->data['collides'] ?? false) {
            // @todo fetch closest node instead of skipping buildings
            return;
        }

        // Clear any previous waypoints (interrupts previous movement if any)
        $previousWaypoints = $this->waypoints;
        $this->waypoints = new SplPriorityQueue();

        $frontier = new SplPriorityQueue();
        $cameFrom = new SplObjectStorage();
        $costSoFar = new SplObjectStorage();

        $current = GameState::$grid->cell((int) $this->pos->x, (int) $this->pos->y);
        if (!$previousWaypoints->isEmpty()) {
            $next = $previousWaypoints->top();
            $current = GameState::$grid->cell((int) $next->x, (int) $next->y);
        }

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

        if ($this->waypoints->isEmpty()) {
            return;
        }

        $nextWaypointCoords = $this->waypoints->top();

        // Ignore Villager's current position
        if ($nextWaypointCoords === $this->pos) {
            $this->waypoints->extract();
            $nextWaypointCoords = $this->waypoints->top();
        }

        $nextCell = GameState::$grid->cell((int) $nextWaypointCoords->x, (int) $nextWaypointCoords->y);
        // Can't walk towards next cell; let's recalculate route
        if ($nextCell->unit !== $this && ($nextCell->data['collides'] ?? false)) {
            $this->updateWaypoints();
        }
    }

    private function walk(Cell $currentCell): void
    {
        if ($this->waypoints->isEmpty()) {
            return;
        }

        $nextWaypointCoords = $this->waypoints->top();
        $nextCell = GameState::$grid->cell((int) $nextWaypointCoords->x, (int) $nextWaypointCoords->y);

        // Block next cell so waypoints between units don't collide
        $nextCell->unit = $this;
        $nextCell->data['collides'] = true;
        if (
            $currentCell->rec->x + $this->shapeTranslation->x === $nextCell->rec->x
            && $currentCell->rec->y + $this->shapeTranslation->y === $nextCell->rec->y
        ) {
            $this->pos = $nextCell->pos;
            $currentCell->unit = null;
            $currentCell->data['collides'] = false;
            $this->shapeTranslation->x = 0;
            $this->shapeTranslation->y = 0;

            // Pops $nextCell out of the waypoints array
            $this->waypoints->extract();
            return;
        }

        // Update screen (x,y) coords
        $this->shapeTranslation->x += $nextCell->pos->x - $this->pos->x;
        $this->shapeTranslation->y += $nextCell->pos->y - $this->pos->y;
    }

    public function draw(): void
    {
        $rec = clone $this->shape;
        $cell = GameState::$grid->cell((int) $this->pos->x, (int) $this->pos->y);

        $rec->x = $cell->rec->x + $this->shapeTranslation->x;
        $rec->y = $cell->rec->y + $this->shapeTranslation->y;

        GameState::$tileset->get(120)->draw($rec, 0, 1, Color::white());
        if ($this->isSelected()) {
            GameState::$tileset->get(120)->draw($rec, 0, 1, Color::red(50));

            if (GameState::$debug) {
                $playerDebugMessage = sprintf("[X=%d, Y=%d]", $this->pos->x, $this->pos->y);
                $playerDebugMessageSize = GameState::$raylib->measureText($playerDebugMessage, 20);

                $x = $rec->x + ($playerDebugMessageSize / 4);

                GameState::$raylib->drawText(
                    $playerDebugMessage,
                    (int) $x,
                    (int) $rec->y,
                    20,
                    Color::white(),
                );
            }
        }
    }
}
