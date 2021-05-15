<?php

declare(strict_types=1);

namespace RTS\Grid;

use DomainException;

trait Grid2DArrayAccessTrait
{
    public function offsetExists($offset)
    {
        return $offset < $this->cells->getSize();
    }

    public function offsetGet($offset)
    {
        return $this->cells[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->cells[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        throw new DomainException('Cannot destroy a grid cell.');
    }
}
