<?php

declare(strict_types=1);

namespace RTS\Objects;

use InvalidArgumentException;
use RTS\GameState;

final class UnitFactory
{
    public function createFromArray(array $unit): Unit
    {
        $cameraZoomScale = 1 / GameState::$camera->zoom;

        switch ($unit['type']) {
            case Building::class:
            case Stone::class:
            case Tree::class:
            case Villager::class:
                $cell = GameState::$grid->cellByWorldCoords(
                    (int) ($unit['x'] + (($unit['collision']['width'] ?? 0) / $cameraZoomScale)),
                    $unit['y'] - 1,
                );

                /** @var Unit $unitObject */
            $unitObject = new $unit['type']($cell->pos);
                if (($unit['properties']['selected'] ?? 'false') === 'true') {
                    $unitObject->select();
                }

                return $unitObject;
        }

        throw new InvalidArgumentException("Unknown object type '{$unit['type']}'.");
    }
}
