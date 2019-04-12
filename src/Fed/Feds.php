<?php
declare(strict_types=1);

namespace Zayso\Fed;

use \ArrayIterator;

class Feds extends ArrayIterator
{
    public function __construct(Feds ...$items)
    {
        parent::__construct($items);
    }
    public function current() : Fed
    {
        return parent::current();
    }
    public function offsetGet($offset) : Fed
    {
        return parent::offsetGet($offset);
    }
}