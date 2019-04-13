<?php declare(strict_types=1);

namespace Zayso\Fed;

use Zayso\Common\Traits\SetterTrait;

/**
 * @property-read string $fedId
 * @property-read string $desc
 * @property-read string $sport
 */
class Fed
{
    use SetterTrait;

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
