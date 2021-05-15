<?php

declare(strict_types=1);

namespace RTS\Scene;

use Nawarian\Raylib\{Raylib, Types\Camera2D, Types\Color, Types\Vector2};
use RTS\Map;

final class TestScene implements Scene
{
    private const CAMERA_SPEED = 20;
    private const MAP_TILESET = __DIR__ . '/../../res/kenney_medievalrtspack/Tilesheet/RTS_medieval@2.png';

    private Raylib $raylib;
    private Map $map;
    private Camera2D $camera;

    public function __construct(Raylib $raylib)
    {
        $this->raylib = $raylib;
    }

    public function create(): void
    {
        $texture = $this->raylib->loadTexture(self::MAP_TILESET);
        $this->map = new Map($this->raylib, $texture);

        $this->camera = new Camera2D(
            new Vector2(0, 0),
            new Vector2(0, 0),
            0,
            .5,
        );
    }

    public function update(): void
    {
        $this->updateCamera();
        $this->map->update();
    }

    private function updateCamera(): void
    {
        $r = $this->raylib;
        $dx = $r->isKeyDown(Raylib::KEY_D) - $r->isKeyDown(Raylib::KEY_A);
        $dy = $r->isKeyDown(Raylib::KEY_S) - $r->isKeyDown(Raylib::KEY_W);

        $this->camera->target->x += $dx * self::CAMERA_SPEED;
        $this->camera->target->y += $dy * self::CAMERA_SPEED;
    }

    public function draw(bool $debug): void
    {
        $r = $this->raylib;

        $r->beginMode2D($this->camera);
            $this->map->draw();

            // Draw cursor
            $cursor = $this->raylib->getScreenToWorld2D($this->raylib->getMousePosition(), $this->camera);
            $highlight = $this->map->grid->cellByWorldCoords((int) $cursor->x, (int) $cursor->y);
            $hightlightColor = Color::orange();
            $hightlightColor->alpha = 100;
            $this->raylib->drawRectangleRec($highlight->rec, $hightlightColor);
        $r->endMode2D();
    }
}
