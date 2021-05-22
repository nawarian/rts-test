<?php

declare(strict_types=1);

namespace RTS;

use Nawarian\Raylib\Raylib;
use Nawarian\Raylib\Types\Camera2D;
use RTS\Grid\Grid2D;
use RTS\Objects\Unit;

final class GameState
{
    public static Raylib $raylib;
    public static Grid2D $grid;
    public static Camera2D $camera;
    public static Spritesheet $tileset;
    public static bool $debug = true;

    public static function update(): void
    {
        if (self::$raylib->isKeyPressed(Raylib::KEY_TAB)) {
            self::$debug = !self::$debug;
        }

        foreach (self::$grid as $cell) {
            $cell->data['collides'] = !is_null($cell->unit);
            $cell->unit && $cell->unit->update();
        }
    }

    public static function add(Unit $unit): void
    {
        $cell = self::$grid->cell((int) $unit->pos->x, (int) $unit->pos->y);
        $cell->unit = $unit;
    }
}
