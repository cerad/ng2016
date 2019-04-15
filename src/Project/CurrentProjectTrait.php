<?php declare(strict_types=1);

namespace Zayso\Project;

trait CurrentProjectTrait
{
    /** @var CurrentProject */
    protected $currentProject;

    /** @required */
    public function setOnceCurrentProject(CurrentProject $currentProject) : void
    {
        $this->currentProject = $this->currentProject ?: $currentProject;
    }
}