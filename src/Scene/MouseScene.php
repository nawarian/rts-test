<?php

declare(strict_types=1);

namespace RTS\Scene;

use Nawarian\Raylib\Raylib;
use Nawarian\Raylib\Types\Color;
use Nawarian\Raylib\Types\Rectangle;
use Nawarian\Raylib\Types\Vector2;
use RTS\Event;
use RTS\GameState;

class MouseScene implements Scene
{
    private Vector2 $start;
    private Vector2 $end;
    private bool $active = false;

    public function __construct()
    {
        $this->start = new Vector2(0, 0);
        $this->end = new Vector2(0, 0);
    }

    public function create(): void
    {
    }

    public function update(): void
    {
        $r = GameState::$raylib;

        if ($r->isMouseButtonDown(Raylib::MOUSE_LEFT_BUTTON)) {
            if ($this->active === false) {
                $this->start = $r->getMousePosition();
                $this->active = true;
            }

            $this->end = $r->getMousePosition();
        }

        if ($r->isMouseButtonReleased(Raylib::MOUSE_LEFT_BUTTON)) {
            $this->active = false;
            $pos1 = GameState::$raylib->getScreenToWorld2D(
                new Vector2(min($this->start->x, $this->end->x), min($this->start->y, $this->end->y)),
                GameState::$camera,
            );

            $pos2 = GameState::$raylib->getScreenToWorld2D(
                new Vector2(abs($this->start->x - $this->end->x), abs($this->start->y - $this->end->y)),
                GameState::$camera,
            );

            Event::emit(Event::MOUSE_AREA_SELECTED, [new Rectangle($pos1->x, $pos1->y, $pos2->x, $pos2->y)]);
        }

        if ($r->isMouseButtonPressed(Raylib::MOUSE_LEFT_BUTTON)) {
            Event::emit(Event::MOUSE_CLICK_LEFT, [$r->getMousePosition()]);
        }

        if ($r->isMouseButtonPressed(Raylib::MOUSE_RIGHT_BUTTON)) {
            Event::emit(Event::MOUSE_CLICK_RIGHT, [$r->getMousePosition()]);
        }
    }

    public function draw(): void
    {
        $r = GameState::$raylib;

        if ($this->active) {
            $r->drawRectangle(
                min($this->start->x, $this->end->x),
                min($this->start->y, $this->end->y),
                abs($this->start->x - $this->end->x),
                abs($this->start->y - $this->end->y),
                Color::blue(50),
            );
        }

        if (GameState::$debug === false) {
            return;
        }

        $cursor = $r->getScreenToWorld2D($r->getMousePosition(), GameState::$camera);
        $highlight = GameState::$grid->cellByWorldCoords((int) $cursor->x, (int) $cursor->y);
        $r->drawRectangleRec($highlight->rec, Color::orange(100));
    }
}