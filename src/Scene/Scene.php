<?php

declare(strict_types=1);

namespace RTS\Scene;

interface Scene
{
    public function create(): void;

    public function update(): void;

    public function draw(): void;
}
