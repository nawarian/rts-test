<?php

declare(strict_types=1);

namespace RTS\Scene;

use InvalidArgumentException;
use Nawarian\Raylib\{Raylib, Types\Camera2D, Types\Color, Types\Rectangle, Types\Vector2};
use RTS\GameState;
use RTS\Grid\Cell;
use RTS\Objects\Unit;
use RTS\Objects\UnitFactory;
use RTS\TiledMapReader;
use RuntimeException;

final class TestScene implements Scene
{
    private const CAMERA_SPEED = 40;
    private const MIN_ZOOM = .4;
    private const MAX_ZOOM = .6;

    private UnitFactory $unitFactory;

    public function __construct(UnitFactory $unitFactory)
    {
        $this->unitFactory = $unitFactory;
    }

    public function create(): void
    {
        GameState::$camera = new Camera2D(
            new Vector2(0, 0),
            new Vector2(0, 0),
            0,
            .5,
        );

        [$grid, $tileset, $units] = TiledMapReader::readFile(__DIR__ . '/../../res/map001.tmx');
        GameState::$grid = $grid;
        GameState::$tileset = $tileset;

        foreach ($units as $unit) {
            try {
                GameState::add($this->unitFactory->createFromArray($unit));
            } catch (InvalidArgumentException $e) {
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

        if ($zoom = GameState::$raylib->getMouseWheelMove()) {
            $zoom /= 10;
            $zoom += GameState::$camera->zoom;
            $zoom = max(self::MIN_ZOOM, $zoom);
            $zoom = min(self::MAX_ZOOM, $zoom);

            GameState::$camera->zoom = $zoom;
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
        $cameraZoomScale = 1 / GameState::$camera->zoom;
        $viewport = new Rectangle(
            GameState::$camera->target->x,
            GameState::$camera->target->y,
            (int) (GameState::$raylib->getScreenWidth() * $cameraZoomScale),
            (int) (GameState::$raylib->getScreenHeight() * $cameraZoomScale),
        );

        $firstIndex = GameState::$grid->indexOfWorldCoords((int) $viewport->x, (int) $viewport->y);
        $lastIndex = GameState::$grid->indexOfWorldCoords(
            (int) ($viewport->x + $viewport->width),
            (int) ($viewport->y + $viewport->height),
        ) + 1;

        $layers = [
            0 => [], // Ground
            1 => [], // Objects
            2 => [], // Debug
        ];
        for ($i = $firstIndex; $i < $lastIndex; ++$i) {
            $cell = GameState::$grid[$i];

            /**
             * Hacky optimisation. The $firstIndex and $lastIndex variables cut
             * a significant portion of the iteration array, but many cells aren't
             * in the viewport.
             *
             * The perfect solution would create slices with multiple $first/$last pairs,
             * each pair representing a grid line that  fits in the viewport.
             */
            if (!GameState::$raylib->checkCollisionRecs($cell->rec, $viewport)) {
                continue;
            }

            $layers[0][] = [
                'sprite' => GameState::$tileset->get($cell->data['gid']),
                'rec' => $cell->rec,
            ];

            if ($cell->unit) {
                $layers[1][] = $cell->unit;
            }

            $layers[2][] = [
                'rec' => $cell->rec,
                'color' => $cell->unit ? Color::red(100) : Color::lime(100),
            ];
        }

        foreach ($layers[0] as $ground) {
            $ground['sprite']->draw($ground['rec'], 0, 1);
        }

        foreach ($layers[1] as $object) {
            $object->draw();
        }

        if (GameState::$debug) {
            foreach ($layers[2] as $debug) {
                GameState::$raylib->drawRectangleRec(
                    $debug['rec'],
                    $debug['color'],
                );
            }
        }
    }

    private function drawCursor(): void
    {
        $cursor = GameState::$raylib->getScreenToWorld2D(GameState::$raylib->getMousePosition(), GameState::$camera);
        $highlight = GameState::$grid->cellByWorldCoords((int) $cursor->x, (int) $cursor->y);
        GameState::$raylib->drawRectangleRec($highlight->rec, Color::orange(100));
    }
}
