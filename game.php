<?php

declare(strict_types=1);

use Nawarian\Raylib\Raylib;
use Nawarian\Raylib\RaylibFactory;
use Nawarian\Raylib\Types\Camera2D;
use Nawarian\Raylib\Types\Color;
use Nawarian\Raylib\Types\Vector2;

require_once __DIR__ . '/vendor/autoload.php';

$factory = new RaylibFactory();
$raylib = $factory->newInstance();

$raylib->initWindow(800, 600, 'RTS Test');
$raylib->setTargetFPS(60);

$map = new Map($raylib);
$camera = new Camera2D(
    new Vector2(0, 0),
    new Vector2(0, 0),
    0,
    1,
);

while (!$raylib->windowShouldClose()) {
    if ($raylib->isKeyDown(Raylib::KEY_A)) {
        $camera->target->x -= 50;
    }

    if ($raylib->isKeyDown(Raylib::KEY_D)) {
        $camera->target->x += 50;
    }

    if ($raylib->isKeyDown(Raylib::KEY_W)) {
        $camera->target->y -= 50;
    }

    if ($raylib->isKeyDown(Raylib::KEY_S)) {
        $camera->target->y += 50;
    }

    if ($camera->target->x < 0) {
        $camera->target->x = 0;
    } elseif ($camera->target->x + 800 > 18 * 64) {
        $camera->target->x = (18 * 64) - 800;
    }

    if ($camera->target->y < 0) {
        $camera->target->y = 0;
    } elseif ($camera->target->y > 16 * 128) {
        $camera->target->y = 16 * 128;
    }

    $raylib->beginDrawing();
        $raylib->clearBackground(Color::black());

        $raylib->beginMode2D($camera);
            $map->render();
        $raylib->endMode2D();

        $raylib->drawFPS(0, 0);
    $raylib->endDrawing();
}

$raylib->closeWindow();
