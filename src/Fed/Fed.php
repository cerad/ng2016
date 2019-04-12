<?php declare(strict_types=1);

namespace Zayso\Fed;

/**
 * @property-read string $fedId
 * @property-read string $desc
 * @property-read string $sport
 */
class Fed
{
    public $fedId;
    public $desc;
    public $sport; // Maybe sports

    public function __construct(
        string $fedId,
        string $desc)
    {
        $this->fedId = $fedId;
        $this->desc  = $desc;
    }
}
