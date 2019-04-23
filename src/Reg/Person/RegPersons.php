<?php
declare(strict_types=1);

namespace Zayso\Reg\Person;

use \ArrayIterator;

class RegPersons extends ArrayIterator
{
    public function __construct(RegPerson ...$items)
    {
        parent::__construct($items);
    }
    public function current() : RegPerson
    {
        return parent::current();
    }
    public function offsetGet($offset) : RegPerson
    {
        return parent::offsetGet($offset);
    }
}