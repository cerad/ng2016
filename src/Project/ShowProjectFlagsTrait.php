<?php declare(strict_types=1);

namespace Zayso\Project;

trait ShowProjectFlagsTrait
{
    /** @var ShowProjectFlags */
    protected $showProjectFlags;

    /** @required */
    public function setOnceShowProjectFlags(ShowProjectFlags $showProjectFlags) : void
    {
        $this->showProjectFlags  = $this->showProjectFlags ?: $showProjectFlags;
    }
}