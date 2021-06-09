<?php

declare(strict_types=1);

namespace RTS\Objects;

use InvalidArgumentException;
use Nawarian\Raylib\Types\Rectangle;
use RTS\GameState;

final class UnitFactory
{
    public function createFromArray(array $unit): Unit
    {
        $cameraZoomScale = 1 / GameState::$camera->zoom;
        $cell = GameState::$grid->cellByWorldCoords(
            (int) ($unit['x'] + (($unit['collision']['width'] ?? 0) / $cameraZoomScale)),
            $unit['y'] - 1,
        );
        $collision = $unit['collision'] ?: [0, 0, 0, 0];

        if (($unit['type'] ?? '') === '') {
            throw new InvalidArgumentException("No type defined for object of id '{$unit['id']}'.");
        }

        /** @var Unit $unitObject */
        $unitObject = new $unit['type'](
            $cell->pos,
            new Rectangle(...$collision),
            GameState::$tileset->get($unit['gid']),
        );

        if (($unit['properties']['selected'] ?? 'false') === 'true') {
            $unitObject->select();
        }

        return $unitObject;
    }
}
