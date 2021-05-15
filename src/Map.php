<?php

declare(strict_types=1);

use Nawarian\Raylib\Raylib;
use Nawarian\Raylib\Types\Color;
use Nawarian\Raylib\Types\Rectangle;
use Nawarian\Raylib\Types\Vector2;

final class Map
{
    private Raylib $raylib;

    public function __construct(Raylib $raylib)
    {
        $this->raylib = $raylib;

        $this->texture = $raylib->loadTexture(__DIR__ . '/../res/kenney_medievalrtspack/Tilesheet/RTS_medieval@2.png');
    }

    public function render(): void
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

        $lines = array_map(
            fn(string $line) => array_map('intval', explode(',', $line)),
            explode(PHP_EOL, $csv)
        );

        $margin = 64;
        $spacing = 64;
        $tileColumns = 18;

        $gridColor = Color::black();
        $gridColor->alpha = 50;

        foreach ($lines as $row => $columns) {
            foreach ($columns as $col => $gid) {
                $tilesetLine = (int) ($gid / $tileColumns);
                $column = ($gid - ($tileColumns * $tilesetLine)) - 1;

                $tilesetTile = new Rectangle($spacing, $spacing, 128, 128);
                $tilesetTile->x += 128 * $column + ($margin * $column);
                $tilesetTile->y += 128 * $tilesetLine + ($margin * $tilesetLine);

                $tile = new Rectangle($col * 64, $row * 64, 64, 64);
                $this->raylib->drawTextureTiled(
                    $this->texture,
                    $tilesetTile,
                    $tile,
                    new Vector2(0, 0),
                    0,
                    .5,
                    Color::white(),
                );
                $this->raylib->drawRectangleLinesEx($tile, 1, $gridColor);
            }
        }

        $objs = [
            ['type' => 'tree', 'gid' => '62', 'x' => 256, 'y' => 128 ],
            ['type' => 'tree', 'gid' => '62', 'x' => 320, 'y' => 1216 ],
            ['type' => 'tree', 'gid' => '62', 'x' => 384, 'y' => 1280 ],
            ['type' => 'tree', 'gid' => '62', 'x' => 288, 'y' => 1280 ],
            ['type' => 'tree', 'gid' => '62', 'x' => 224, 'y' => 1216 ],
            ['type' => 'tree', 'gid' => '62', 'x' => 224, 'y' => 1312 ],
            ['type' => 'tree', 'gid' => '62', 'x' => 288, 'y' => 1344 ],
            ['type' => 'tree', 'gid' => '62', 'x' => 352, 'y' => 1344 ],
            ['type' => 'stone', 'gid' => '80', 'x' => 1536, 'y' => 1024 ],
            ['type' => 'building', 'gid' => '115', 'x' => 896, 'y' => 960 ],
            ['type' => 'villager', 'gid' => '121', 'x' => 896, 'y' => 1056 ],
            ['type' => 'villager', 'gid' => '121', 'x' => 960, 'y' => 1088 ],
            ['type' => 'villager', 'gid' => '121', 'x' => 832, 'y' => 1088 ],
        ];

        foreach ($objs as $obj) {
            $tilesetTile = new Rectangle($spacing, $spacing, 128, 128);
            $col = 0;
            $row = 0;
            switch ($obj['type']) {
                case 'tree':
                    $col = 4;
                    $row = 3;
                    break;
                case 'villager':
                    $col = 11;
                    $row = 6;
                    break;
                case 'building':
                    $col = 5;
                    $row = 6;
                    break;
                case 'stone':
                    $col = 5;
                    $row = 4;
                    break;
            }

            $tilesetTile->x += 128 * $col + ($margin * $col);
            $tilesetTile->y += 128 * $row + ($margin * $row);

            $this->raylib->drawTextureTiled(
                $this->texture,
                $tilesetTile,
                new Rectangle($obj['x'] / 2, $obj['y'] / 2, 64, 64),
                new Vector2(0, 0),
                0,
                .5,
                Color::white(),
            );

            $this->raylib->drawRectangleLinesEx(
                new Rectangle($obj['x'] / 2, $obj['y'] / 2, 64, 64),
                1,
                Color::red()
            );
        }
    }
}
