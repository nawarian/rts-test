<?php

declare(strict_types=1);

namespace RTS\Objects;

use Nawarian\Raylib\Raylib;
use Nawarian\Raylib\Types\Color;
use Nawarian\Raylib\Types\Rectangle;
use Nawarian\Raylib\Types\Vector2;
use RTS\Event;
use RTS\GameState;
use RTS\Grid\Cell;
use RTS\Sprite;
use SplObjectStorage;
use SplPriorityQueue;
use function RTS\manhattanDistance;

class Villager extends Unit
{
    private const WIDTH = 128;
    private const HEIGHT = 128;

    private int $turnsPerSecond = 5;
    private Rectangle $shape;
    private Vector2 $shapeTranslation;
    private SplPriorityQueue $waypoints;

    private int $timeAcc = 0;

    public function __construct(Vector2 $pos, Rectangle $collision, Sprite $sprite)
    {
        parent::__construct($pos, $collision, $sprite);
        $this->shape = new Rectangle(0, 0, self::WIDTH, self::HEIGHT);
        $this->shapeTranslation = new Vector2(0, 0);
        $this->waypoints = new SplPriorityQueue();

        Event::on(Event::COMMAND_MOVE, [$this, 'handleMoveCommand']);
        Event::on(Event::MOUSE_AREA_SELECTED, [$this, 'handleAreaSelected']);
    }

    public function handleAreaSelected(Rectangle $area): void
    {
        $cell = GameState::$grid->cell((int) $this->pos->x, (int) $this->pos->y);

        if (GameState::$raylib->checkCollisionRecs($cell->rec, $area)) {
            $this->select();
        } else {
            $this->deselect();
        }
    }

    public function handleMoveCommand(int $x, int $y): void
    {
        if ($this->isSelected()) {
            $this->moveTo($x, $y);
        }
    }

    public function update(): void
    {
        $currentCell = GameState::$grid->cell((int) $this->pos->x, (int) $this->pos->y);

        $this->toggleSelection($currentCell);
        $this->updateWaypoints();

        $this->timeAcc += (int) (GameState::$raylib->getFrameTime() * 1000);
        if ($this->timeAcc > (1000 / $this->turnsPerSecond)) {
            $this->timeAcc = 0;
            $this->step($currentCell);
        }
    }

    private function step(Cell $currentCell): void
    {
        $this->walk($currentCell);
    }

    private function toggleSelection(Cell $currentCell): void
    {
        if (GameState::$raylib->isMouseButtonPressed(Raylib::MOUSE_LEFT_BUTTON)) {
            $clickedCoords = GameState::$raylib->getScreenToWorld2D(
                GameState::$raylib->getMousePosition(),
                GameState::$camera,
            );

            $rec = clone $this->collision;
            $rec->x = $currentCell->rec->x + $this->shapeTranslation->x + $this->collision->x;
            $rec->y = $currentCell->rec->y + $this->shapeTranslation->y + $this->collision->y;

            if (GameState::$raylib->checkCollisionPointRec($clickedCoords, $rec)) {
                $this->select();
            } elseif ($this->isSelected()) {
                $this->deselect();
            }
        }
    }

    private function updateWaypoints(bool $forceRouteUpdate = false): void
    {
        if (
            !$forceRouteUpdate
            && (!$this->isSelected() || !GameState::$raylib->isMouseButtonPressed(Raylib::MOUSE_RIGHT_BUTTON))
        ) {
            return;
        }

        $clickedCoords = GameState::$raylib->getScreenToWorld2D(
            GameState::$raylib->getMousePosition(),
            GameState::$camera,
        );
        $goal = GameState::$grid->cellByWorldCoords((int) $clickedCoords->x, (int) $clickedCoords->y);
        $this->moveTo((int) $goal->pos->x, (int) $goal->pos->y);
    }

    private function moveTo(int $x, int $y): void
    {
        $goal = GameState::$grid->cell($x, $y);
        if ($goal->data['collides'] ?? false) {
            // @todo fetch closest node instead of skipping buildings
            return;
        }

        // Clear any previous waypoints (interrupts previous movement if any)
        $current = GameState::$grid->cell((int) $this->pos->x, (int) $this->pos->y);
        if (!$this->waypoints->isEmpty()) {
            $next = $this->waypoints->top();
            $current = GameState::$grid->cell((int) $next->x, (int) $next->y);
        }

        $this->waypoints = GameState::$grid->findPath($current, $goal, $this->turnsPerSecond);
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
            $this->updateWaypoints(true);
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
            GameState::$raylib->checkCollisionRecs(
                new Rectangle(
                    $nextCell->rec->x + $this->collision->x,
                    $nextCell->rec->y + $this->collision->y,
                    $this->collision->width,
                    $this->collision->height
                ),
                $nextCell->rec,
            )
        ) {
            $this->pos = $nextCell->pos;
            $currentCell->unit = null;
            $currentCell->data['collides'] = false;

            // Pops $nextCell out of the waypoints array
            $this->waypoints->extract();
        }
    }

    public function draw(): void
    {
        $rec = clone $this->shape;
        $cell = GameState::$grid->cell((int) $this->pos->x, (int) $this->pos->y);

        $rec->x = $cell->rec->x + $this->shapeTranslation->x;
        $rec->y = $cell->rec->y + $this->shapeTranslation->y;

        GameState::$debug && $this->isSelected() && $this->drawPathLabels();
        $this->sprite->draw($rec, 0, 1, Color::white());
        if ($this->isSelected()) {
            $this->sprite->draw($rec, 0, 1, Color::red(50));

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

                GameState::$raylib->drawRectangleLinesEx(
                    new Rectangle(
                        $rec->x + $this->collision->x,
                        $rec->y + $this->collision->y,
                        $this->collision->width,
                        $this->collision->height,
                    ),
                    2,
                    Color::red(),
                );
            }
        }
    }

    private function drawPathLabels(): void
    {
        $cell = GameState::$grid->cell((int) $this->pos->x, (int) $this->pos->y);
        $mouseCoords = GameState::$raylib->getScreenToWorld2D(
            GameState::$raylib->getMousePosition(),
            GameState::$camera,
        );
        $goal = GameState::$grid->cellByWorldCoords((int) $mouseCoords->x, (int) $mouseCoords->y);

        $path = GameState::$grid->findPath($cell, $goal, $this->turnsPerSecond);
        $path->setExtractFlags(SplPriorityQueue::EXTR_BOTH);

        do {
            if ($path->isEmpty()) {
                break;
            }

            $pathData = $path->extract();
            $priority = $pathData['priority'];
            $coords = $pathData['data'];
            $cell = GameState::$grid->cell((int) $coords->x, (int) $coords->y);

            $text = "{$priority}";
            $textSize = GameState::$raylib->measureText($text, 40);
            GameState::$raylib->drawText(
                $text,
                (int) ($cell->rec->x + ($cell->rec->width / 2) - ($textSize / 2)),
                (int) ($cell->rec->y + ($cell->rec->height / 2) - (40 / 2)),
                40,
                Color::white(),
            );
        } while (true);
    }
}
