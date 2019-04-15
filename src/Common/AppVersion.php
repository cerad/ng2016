<?php declare(strict_types=1);

namespace Zayso\Common;

class AppVersion
{
    private $version;

    public function __construct(string $version)
    {
        $this->version= $version;
    }
    public function __toString() : string
    {
        return $this->version;
    }
}