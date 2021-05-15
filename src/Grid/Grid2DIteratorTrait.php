<?php

declare(strict_types=1);

namespace RTS\Grid;

trait Grid2DIteratorTrait
{
    private int $cur = 0;

    public function current()
    {
        return $this->cells[$this->cur];
    }

    public function next()
    {
        $this->cur++;
    }

    public function key()
    {
        return $this->cur;
    }

    public function valid()
    {
        return $this->cur < $this->cells->getSize();
    }

    public function rewind()
    {
        $this->cur = 0;
    }
}