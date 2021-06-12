<?php

declare(strict_types=1);

namespace RTS\Scene;

use Nawarian\Raylib\Raylib;
use Nawarian\Raylib\Types\Color;
use Nawarian\Raylib\Types\Rectangle;
use RTS\Event;
use RTS\GameState;

class ConsoleScene implements Scene
{
    private int $frameTimeAcc = 0;
    private string $buffer = '';
    private array $commands = [];

    public function create(): void
    {
        Event::on(Event::COMMAND_DISPATCH, fn(string $cmd) => $this->handleCommandDispatched($cmd));
    }

    private function handleCommandDispatched(string $commandLine): void
    {
        $parts = explode(' ', $commandLine);
        $command = array_shift($parts);

        switch ($command) {
            case '/c':
            case '/close':
                GameState::$typing = false;
                break;
            case '/m':
            case '/move':
                [$x, $y] = $parts;
                Event::emit(Event::COMMAND_MOVE, [(int) $x, (int) $y]);
                break;
            case '/q':
            case '/quit':
                Event::emit(Event::LOOP_INTERRUPT);
                break;
            default:
                $commandLine = implode(' ', [$commandLine, '(Unknown command)']);
                break;
        }

        $this->commands[] = $commandLine;

        if (count($this->commands) > 5) {
            array_shift($this->commands);
        }
    }

    public function update(): void
    {
        if (GameState::$typing === false && GameState::$raylib->isKeyPressed(Raylib::KEY_ENTER)) {
            GameState::$typing = true;
            return;
        }

        $this->frameTimeAcc += (int) (GameState::$raylib->getFrameTime() * 1000);
        if ($this->frameTimeAcc > 1000) {
            $this->frameTimeAcc = 0;
        }

        if (GameState::$typing === false) {
            return;
        }

        if ($this->buffer !== '' && GameState::$raylib->isKeyDown(Raylib::KEY_BACKSPACE)) {
            $this->buffer = substr($this->buffer, 0, -1);
        }

        $key = GameState::$raylib->getKeyPressed();
        if (in_array($key, range(65, 90))) {
            if (GameState::$raylib->isKeyDown(Raylib::KEY_LEFT_SHIFT) === false) {
                $key += 32;
            }

            $char = chr($key);
            $this->buffer .= $char;
        } elseif(in_array($key, range(48, 57))) {
            $this->buffer .= chr($key);
        } elseif ($key === Raylib::KEY_SLASH) {
            $this->buffer .= '/';
        } elseif ($key === Raylib::KEY_SPACE) {
            $this->buffer .= ' ';
        }

        if (GameState::$typing && GameState::$raylib->isKeyPressed(Raylib::KEY_ENTER)) {
            Event::emit(Event::COMMAND_DISPATCH, [$this->buffer]);
            $this->buffer = '';
        }
    }

    public function draw(): void
    {
        if (GameState::$typing === false) {
            return;
        }

        $r = GameState::$raylib;
        $fontSize = 15;
        $margin = 5;

        $consoleRec = new Rectangle(
            $margin,
            $r->getScreenHeight() - ($r->getScreenHeight() * .2),
            $r->getScreenWidth() * .8,
            ($r->getScreenHeight() * .2) - $margin,
        );

        $r->drawRectangleRec($consoleRec, Color::black(100));

        foreach ($this->commands as $n => $commandLine) {
            $r->drawText(
                $commandLine,
                (int) $consoleRec->x,
                (int) ($consoleRec->y + ($n * $fontSize)),
                $fontSize,
                Color::white(),
            );
        }

        $consoleRec->height *= .2;
        $consoleRec->y = $r->getScreenHeight() - $consoleRec->height - $margin;

        $r->drawRectangleRec($consoleRec, Color::white(50));

        if ($this->frameTimeAcc > 500) {
            $bufferSize = $r->measureText($this->buffer, $fontSize);

            $r->drawLine(
                (int) $consoleRec->x + ($margin * 2) + $bufferSize,
                (int) $consoleRec->y + $margin,
                (int) $consoleRec->x + ($margin * 2) + $bufferSize,
                (int) ($consoleRec->y + $consoleRec->height) - $margin,
                Color::white()
            );
        }

        $r->drawText(
            $this->buffer,
            (int) ($consoleRec->x + $margin),
            (int) ($consoleRec->y + $consoleRec->height) - $margin - $fontSize,
            $fontSize,
            Color::white(),
        );
    }
}