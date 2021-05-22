<?php

declare(strict_types=1);

use Nawarian\Raylib\Raylib;
use Psr\Container\ContainerInterface;
use RTS\GameLoop;
use RTS\GameState;
use RTS\Scene\TestScene;

require_once __DIR__ . '/vendor/autoload.php';

/** @var ContainerInterface $di */
$di = require __DIR__ . '/di.php';

$raylib = $di->get(Raylib::class);
GameState::$raylib = $raylib;

/** @var GameLoop $game */
$game = $di->get(GameLoop::class);
$game->withScreenWidth(800)
    ->withScreenHeight(600)
    ->withScene($di->get(TestScene::class))
    ->start();
