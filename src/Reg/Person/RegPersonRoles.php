<?php
declare(strict_types=1);

namespace Zayso\Reg\Person;

use \ArrayIterator;

class RegPersonRoles extends ArrayIterator
{
    public function __construct(RegPersonRole ...$items)
    {
        parent::__construct($items);
    }
    public function current() : RegPersonRole
    {
        return parent::current();
    }
    public function offsetGet($offset) : RegPersonRole
    {
        return parent::offsetGet($offset);
    }
}