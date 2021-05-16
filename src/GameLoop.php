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
    private Scene $scene;

    public function __construct(Raylib $raylib)
    {
        $this->raylib = $raylib;
    }

    public function start(): void
    {
        $r = $this->raylib;
        $r->initWindow($this->screenWidth, $this->screenHeight, self::GAME_TITLE);
        $r->setTargetFPS($this->targetFPS);

        $this->scene->create();
        while ($this->running) {
            $this->running = $this->running && !$r->windowShouldClose();

            $this->scene->update();

            $r->beginDrawing();
                $r->clearBackground(Color::black());

                $this->scene->draw();
            $r->endDrawing();
        }

        $r->closeWindow();
    }

    public function withScene(Scene $scene): self
    {
        $this->scene = $scene;
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
