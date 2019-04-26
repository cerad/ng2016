<?php declare(strict_types=1);

namespace Zayso\Common\Traits;

trait EscapeTrait
{
    protected function escape(?string $content) : string
    {
        return $content !== null ? htmlspecialchars($content, ENT_COMPAT) : '';
    }
}