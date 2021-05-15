<?php

declare(strict_types=1);

namespace RTS;

use Nawarian\Raylib\Raylib;
use Nawarian\Raylib\Types\Camera2D;
use Nawarian\Raylib\Types\Color;
use Nawarian\Raylib\Types\Texture2D;
use Nawarian\Raylib\Types\Vector2;
use RTS\Grid\Cell;
use RTS\Grid\Grid2D;
use RTS\Objects\Villager;

final class Map
{
    private Raylib $raylib;
    public Grid2D $grid;
    private Spritesheet $tileset;
    private Camera2D $camera;
    private array $units = [];

    public function __construct(Raylib $raylib, Texture2D $texture, Camera2D $camera)
    {
        $this->raylib = $raylib;
        $this->camera = $camera;

        $this->initGrid();

        $this->tileset = new Spritesheet(
            $this->raylib,
            $texture,
            64,
            64,
            128,
            128,
        );

        $this->units[] = new Villager($this->raylib, $this->camera, $this->grid, new Vector2(7, 6));
    }

    private function initGrid(): void
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

        $this->grid = new Grid2D(count($rows[0]), count($rows));
        $c = 0;
        foreach ($rows as $row => $columns) {
            foreach ($columns as $column => $gid) {
                $this->grid[$c++]->data['gid'] = $gid;
            }
        }
    }

    public function update(): void
    {
        foreach ($this->units as $unit) {
            $unit->update();
        }
    }

    public function draw(): void
    {
        $gridColor = Color::black();
        $gridColor->alpha = 50;

        /** @var Cell $cell */
        foreach ($this->grid as $cell) {
            $this->tileset
                ->get($cell->data['gid'])
                ->draw($cell->rec, 0, 1);
             $this->raylib->drawRectangleLinesEx($cell->rec, 1, $gridColor);
        }

        foreach ($this->units as $unit) {
            $unit->draw();
        }
    }
}
