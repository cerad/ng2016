<?php declare(strict_types=1);

namespace Zayso\Common\Traits;

trait EscapeTrait
{
    protected function escape($content)
    {
        return htmlspecialchars($content, ENT_COMPAT);
    }
}