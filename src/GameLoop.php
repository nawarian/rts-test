<?php

declare(strict_types=1);

namespace RTS;

use Nawarian\Raylib\Raylib;
use Nawarian\Raylib\Types\Color;
use RTS\Scene\Scene;

final class GameLoop
{
    private const GAME_TITLE = 'RTS PoC';

    private int $screenWidth;
    private int $screenHeight;
    private int $targetFPS = 60;
    private bool $running = true;

    private Raylib $raylib;

    public function __construct(Raylib $raylib)
    {
        $this->raylib = $raylib;
    }

    public function start(): void
    {
        $this->registerEventHandlers();

        $r = $this->raylib;
        $r->initWindow($this->screenWidth, $this->screenHeight, self::GAME_TITLE);
        $r->setTargetFPS($this->targetFPS);

        Event::emit(Event::LOOP_CREATE);
        while ($this->running) {
            $this->running = $this->running && !$r->windowShouldClose();

            Event::emit(Event::LOOP_UPDATE);

            $r->beginDrawing();
                $r->clearBackground(Color::black());

                Event::emit(Event::LOOP_DRAW);
            $r->endDrawing();
        }

        $r->closeWindow();
    }

    private function registerEventHandlers(): void
    {
        Event::on(Event::LOOP_INTERRUPT, fn() => $this->running = false);
    }

    public function withScene(Scene $scene): self
    {
        Event::removeAllListeners(Event::LOOP_CREATE);
        Event::removeAllListeners(Event::LOOP_DRAW);
        Event::removeAllListeners(Event::LOOP_UPDATE);

        Event::on(Event::LOOP_CREATE, [$scene, 'create']);
        Event::on(Event::LOOP_DRAW, [$scene, 'draw']);
        Event::on(Event::LOOP_UPDATE, [$scene, 'update']);

        return $this;
    }

    public function withScreenWidth(int $width): self
    {
        $this->screenWidth = $width;
        return $this;
    }

    public function withScreenHeight(int $height): self
    {
        $this->screenHeight = $height;
        return $this;
    }
}
