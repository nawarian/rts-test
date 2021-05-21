<?php

declare(strict_types=1);

namespace RTS\Scene;

use Nawarian\Raylib\{Raylib, Types\Camera2D, Types\Color, Types\Vector2};
use RTS\GameState;
use RTS\Grid\Cell;
use RTS\Grid\Grid2D;
use RTS\Objects\Building;
use RTS\Objects\Villager;
use RTS\Spritesheet;

final class TestScene implements Scene
{
    private const CAMERA_SPEED = 20;
    private const MAP_TILESET = __DIR__ . '/../../res/kenney_medievalrtspack/Tilesheet/RTS_medieval@2.png';

    private Raylib $raylib;
    private Spritesheet $tileset;
    private GameState $state;

    public function __construct(Raylib $raylib)
    {
        $this->raylib = $raylib;
    }

    private function buildGrid(): Grid2D
    {
        $csv = <<<CSV
            1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,
            1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,
            1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,
            1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,
            1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,
            1,1,1,1,1,23,6,6,6,24,1,1,1,1,1,1,
            1,1,1,1,1,5,1,1,1,5,1,1,1,1,1,1,
            1,1,1,23,6,26,1,1,1,5,1,1,1,1,1,1,
            1,1,1,44,1,41,6,6,6,42,1,1,1,1,1,1,
            1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,
            1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,
            1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,
            1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,
            1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,
            1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,
            1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1
        CSV;

        $rows = array_map(
            fn(string $line) => array_map('intval', array_filter(explode(',', $line))),
            explode(PHP_EOL, $csv)
        );

        $grid = new Grid2D(count($rows[0]), count($rows));
        $c = 0;
        foreach ($rows as $row => $columns) {
            foreach ($columns as $column => $gid) {
                $grid[$c++]->data['gid'] = $gid;
            }
        }

        return $grid;
    }

    public function create(): void
    {
        $grid = $this->buildGrid();
        $camera = new Camera2D(
            new Vector2(0, 0),
            new Vector2(0, 0),
            0,
            .5,
        );

        $this->state = new GameState($this->raylib, $grid, $camera);

        $texture = $this->raylib->loadTexture(self::MAP_TILESET);
        $this->tileset = new Spritesheet(
            $this->state->raylib,
            $texture,
            64,
            64,
            128,
            128,
        );

        $this->state->add(new Villager($this->state, new Vector2(4, 4), $this->tileset));
        $this->state->add(new Building($this->state, new Vector2(5, 5), $this->tileset));
        $this->state->add(new Building($this->state, new Vector2(5, 6), $this->tileset));
        $this->state->add(new Building($this->state, new Vector2(6, 6), $this->tileset));
        $this->state->add(new Building($this->state, new Vector2(7, 6), $this->tileset));
        $this->state->add(new Building($this->state, new Vector2(8, 6), $this->tileset));
        $this->state->add(new Building($this->state, new Vector2(9, 6), $this->tileset));
        $this->state->add(new Building($this->state, new Vector2(9, 5), $this->tileset));
    }

    public function update(): void
    {
        $r = $this->raylib;
        $dx = $r->isKeyDown(Raylib::KEY_D) - $r->isKeyDown(Raylib::KEY_A);
        $dy = $r->isKeyDown(Raylib::KEY_S) - $r->isKeyDown(Raylib::KEY_W);

        $this->state->camera->target->x += $dx * self::CAMERA_SPEED;
        $this->state->camera->target->y += $dy * self::CAMERA_SPEED;

        $this->state->update();
    }

    public function draw(): void
    {
        $r = $this->raylib;


        // Draw debug stats
        if ($this->state->debug) {
            $r->drawFPS(0, 0);

            $text = sprintf('Mem: %04d Kb', memory_get_usage(true) / 1024);
            $textSize = $r->measureText($text, 20);
            $r->drawText(
                $text,
                800 - $textSize,
                600 - 20,
                20,
                Color::lime(),
            );
        }

        $r->beginMode2D($this->state->camera);
            $this->drawMap();
            $this->drawCursor();
        $r->endMode2D();
    }

    private function drawMap(): void
    {
        $debug = $this->state->debug;
        $gridColor = Color::black();
        $gridColor->alpha = 20;

        /** @var Cell $cell */
        foreach ($this->state->grid as $cell) {
            $this->tileset
                ->get($cell->data['gid'])
                ->draw($cell->rec, 0, 1);
            $this->state->raylib->drawRectangleLinesEx($cell->rec, 1, $gridColor);
        }

        foreach ($this->state->grid as $cell) {
            $gridDebugColor = Color::lime(100);
            if ($cell->unit) {
                $gridDebugColor = Color::red(100);
                $cell->unit->draw();
            }

            $debug && $this->raylib->drawRectangleRec($cell->rec, $gridDebugColor);
        }
    }

    private function drawCursor(): void
    {
        $cursor = $this->raylib->getScreenToWorld2D($this->raylib->getMousePosition(), $this->state->camera);
        $highlight = $this->state->grid->cellByWorldCoords((int)$cursor->x, (int)$cursor->y);
        $hightlightColor = Color::orange();
        $hightlightColor->alpha = 100;
        $this->raylib->drawRectangleRec($highlight->rec, $hightlightColor);
    }
}
