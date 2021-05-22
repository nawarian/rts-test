<?php

declare(strict_types=1);

namespace RTS\Scene;

use Nawarian\Raylib\{Raylib, Types\Camera2D, Types\Color, Types\Vector2};
use RTS\GameState;
use RTS\Grid\Cell;
use RTS\TiledMapReader;
use RuntimeException;

final class TestScene implements Scene
{
    private const CAMERA_SPEED = 20;

    public function create(): void
    {
        GameState::$camera = new Camera2D(
            new Vector2(0, 0),
            new Vector2(0, 0),
            0,
            .5,
        );

        [$grid, $tileset, $units] = TiledMapReader::readFile(__DIR__ . '/../../res/test.tmx');
        GameState::$grid = $grid;
        GameState::$tileset = $tileset;

        foreach ($units as $unit) {
            $cell = GameState::$grid->cellByWorldCoords($unit['x'], $unit['y']);
            $unitClassName = $unit['type'];

            if (class_exists($unitClassName)) {
                GameState::add(new $unitClassName($cell->pos));
            } else {
                throw new RuntimeException(
                    "Could not find unit of type '{$unitClassName}' (x = {$unit['x']}; y = {$unit['y']})."
                );
            }
        }
    }

    public function update(): void
    {
        $r = GameState::$raylib;
        $dx = $r->isKeyDown(Raylib::KEY_D) - $r->isKeyDown(Raylib::KEY_A);
        $dy = $r->isKeyDown(Raylib::KEY_S) - $r->isKeyDown(Raylib::KEY_W);

        $cameraTarget = GameState::$camera->target;
        $cameraTarget->x += $dx * self::CAMERA_SPEED;
        $cameraTarget->y += $dy * self::CAMERA_SPEED;

        if ($cameraTarget->x < 0) {
            $cameraTarget->x = 0;
        }

        if ($cameraTarget->y < 0) {
            $cameraTarget->y = 0;
        }

        $cellCount = GameState::$grid->cells->count();
        $screenWidth = $r->getScreenWidth();
        $screenHeight = $r->getScreenHeight();
        /** @var Cell $lastCell */
        $lastCell = GameState::$grid->cells[$cellCount - 1];
        if ($cameraTarget->x + $screenWidth * 2 > $lastCell->rec->x + $lastCell->rec->width) {
            $cameraTarget->x = $lastCell->rec->x + $lastCell->rec->width - $screenWidth * 2;
        }

        if ($cameraTarget->y + $screenHeight * 2 > $lastCell->rec->y + $lastCell->rec->height) {
            $cameraTarget->y = $lastCell->rec->y + $lastCell->rec->height - $screenHeight * 2;
        }

        GameState::update();
    }

    public function draw(): void
    {
        $r = GameState::$raylib;

        $r->beginMode2D(GameState::$camera);
            $this->drawMap();
            $this->drawCursor();
        $r->endMode2D();

        // Draw debug stats
        if (GameState::$debug) {
            $text = sprintf('FPS %02d', $r->getFPS());
            $r->drawText(
                $text,
                5,
                5,
                20,
                Color::white(),
            );

            $text = sprintf('Mem: %04d Kb', memory_get_usage(true) / 1024);
            $textSize = $r->measureText($text, 20);
            $r->drawText(
                $text,
                800 - $textSize - 10,
                600 - 20,
                20,
                Color::white(),
            );
        }
    }

    private function drawMap(): void
    {
        $debug = GameState::$debug;
        $gridColor = Color::black();
        $gridColor->alpha = 20;

        /** @var Cell $cell */
        foreach (GameState::$grid as $cell) {
            GameState::$tileset
                ->get($cell->data['gid'])
                ->draw($cell->rec, 0, 1);
            GameState::$raylib->drawRectangleLinesEx($cell->rec, 1, $gridColor);
        }

        foreach (GameState::$grid as $cell) {
            $gridDebugColor = Color::lime(100);
            if ($cell->unit) {
                $gridDebugColor = Color::red(100);
                $cell->unit->draw();
            }

            $debug && GameState::$raylib->drawRectangleRec($cell->rec, $gridDebugColor);
        }
    }

    private function drawCursor(): void
    {
        $cursor = GameState::$raylib->getScreenToWorld2D(GameState::$raylib->getMousePosition(), GameState::$camera);
        $highlight = GameState::$grid->cellByWorldCoords((int)$cursor->x, (int)$cursor->y);
        $hightlightColor = Color::orange();
        $hightlightColor->alpha = 100;
        GameState::$raylib->drawRectangleRec($highlight->rec, $hightlightColor);
    }
}
