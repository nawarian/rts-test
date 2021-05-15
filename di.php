<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Nawarian\Raylib\{Raylib, RaylibFactory};
use Psr\Container\ContainerInterface;
use RTS\Game;
use RTS\Grid\Grid2D;
use RTS\Map;
use RTS\Scene\TestScene;
use function DI\autowire;

$builder = new ContainerBuilder();

$builder->addDefinitions([
    Game::class => function (ContainerInterface $c) {
        $raylib = $c->get(Raylib::class);

        return new Game($raylib);
    },

    TestScene::class => autowire(TestScene::class),

    // Vendor
    Raylib::class => function () {
        $factory = $factory = new RaylibFactory();
        return $factory->newInstance();
    },
]);

return $builder->build();
