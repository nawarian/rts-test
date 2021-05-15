<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Nawarian\Raylib\{Raylib, RaylibFactory};
use Psr\Container\ContainerInterface;
use RTS\GameLoop;
use RTS\Grid\Grid2D;
use RTS\Map;
use RTS\Scene\TestScene;
use function DI\autowire;

$builder = new ContainerBuilder();

$builder->addDefinitions([
    GameLoop::class => function (ContainerInterface $c) {
        $raylib = $c->get(Raylib::class);

        return new GameLoop($raylib);
    },

    TestScene::class => autowire(TestScene::class),

    // Vendor
    Raylib::class => function () {
        $factory = $factory = new RaylibFactory();
        return $factory->newInstance();
    },
]);

return $builder->build();
