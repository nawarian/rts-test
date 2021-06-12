<?php

declare(strict_types=1);

namespace RTS;

use Evenement\EventEmitter;
use Evenement\EventEmitterInterface;

/**
 * @method static void on($event, callable $listener);
 * @method static void onceBefore($event, callable $listener);
 * @method static void once($event, callable $listener);
 * @method static void removeListener($event, callable $listener);
 * @method static void removeAllListeners($event = null);
 * @method static void listeners($event = null);
 * @method static void emit($event, array $arguments = []);
 * @method static void forward(EventEmitterInterface $emitter);
 */
class Event
{
    public const LOOP_CREATE = 'loop.create';
    public const LOOP_DRAW = 'loop.draw';
    public const LOOP_INTERRUPT = 'loop.interrupt';
    public const LOOP_UPDATE = 'loop.update';
    public const COMMAND_DISPATCH = 'command.dispatch';
    public const COMMAND_MOVE = 'command.move';
    public const MOUSE_AREA_SELECTED = 'mouse.area_selected';
    public const MOUSE_CLICK_LEFT = 'mouse.click.left';
    public const MOUSE_CLICK_RIGHT = 'mouse.click.right';

    private static ?EventEmitterInterface $instance = null;

    public static function __callStatic(string $name, array $arguments)
    {
        if (self::$instance === null) {
            self::$instance = new EventEmitter();
        }

        return call_user_func_array([self::$instance, $name], $arguments);
    }
}
