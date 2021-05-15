<?php

declare(strict_types=1);

namespace RTS;

use Nawarian\Raylib\Raylib;
use Nawarian\Raylib\Types\Color;
use Nawarian\Raylib\Types\Rectangle;
use Nawarian\Raylib\Types\Texture2D;
use RTS\Grid\Cell;
use RTS\Grid\Grid2D;

final class Map
{
    private Raylib $raylib;
    private Grid2D $grid;
    private Spritesheet $tileset;

    public function __construct(Raylib $raylib, Texture2D $texture)
    {
        $this->raylib = $raylib;

        $this->initGrid();

        $this->tileset = new Spritesheet(
            $this->raylib,
            $texture,
            64,
            64,
            128,
            128,
        );
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

    public function draw(): void
    {
        $gridColor = Color::black();
        $gridColor->alpha = 50;

        $cellSize = 128;
        $cellRec = new Rectangle(0, 0, $cellSize, $cellSize);
        /** @var Cell $cell */
        foreach ($this->grid as $cell) {
            $cellRec->x = $cell->x * $cellSize;
            $cellRec->y = $cell->y * $cellSize;

            $this->tileset
                ->get($cell->data['gid'])
                ->draw($cellRec, 0, 1);
             $this->raylib->drawRectangleLinesEx($cellRec, 1, $gridColor);
        }
    }
}
