<?php

declare(strict_types=1);

namespace RTS;

use Nawarian\Raylib\Types\Vector2;

function manhattanDistance(Vector2 $a, Vector2 $b): int
{
    $dx = abs($b->x - $a->x);
    $dy = abs($b->y - $a->y);

    return (int) ($dx + $dy);
}
