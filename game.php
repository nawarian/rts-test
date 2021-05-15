<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use RTS\Game;
use RTS\Scene\TestScene;

require_once __DIR__ . '/vendor/autoload.php';

/** @var ContainerInterface $di */
$di = require __DIR__ . '/di.php';

/** @var Game $game */
$game = $di->get(Game::class);
$game->withScreenWidth(800)
    ->withScreenHeight(600)
    ->withScene($di->get(TestScene::class))
    ->start();
