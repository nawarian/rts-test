<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use RTS\GameLoop;
use RTS\Scene\TestScene;

require_once __DIR__ . '/vendor/autoload.php';

/** @var ContainerInterface $di */
$di = require __DIR__ . '/di.php';

/** @var GameLoop $game */
$game = $di->get(GameLoop::class);
$game->withScreenWidth(800)
    ->withScreenHeight(600)
    ->withScene($di->get(TestScene::class))
    ->start();
