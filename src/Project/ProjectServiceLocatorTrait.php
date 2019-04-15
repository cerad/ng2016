<?php declare(strict_types=1);

namespace Zayso\Project;

trait ProjectServiceLocatorTrait
{
    /** @var ProjectServiceLocator */
    protected $projectServiceLocator;

    /** @required */
    public function setOnceProjectServiceLocator(ProjectServiceLocator $projectServiceLocator) : void
    {
        $this->projectServiceLocator = $this->projectServiceLocator ?: $projectServiceLocator;
    }
}