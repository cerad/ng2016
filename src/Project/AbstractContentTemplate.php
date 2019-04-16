<?php declare(strict_types=1);

namespace Zayso\Project;

use Zayso\Common\Traits\EscapeTrait;
use Zayso\Common\Traits\RouterTrait;

abstract class AbstractContentTemplate implements ProjectServiceInterface
{
    use EscapeTrait;
    use RouterTrait;

    protected $currentProject;

    public function __construct(CurrentProject $currentProject)
    {
        $this->currentProject = $currentProject;
    }

    abstract public function render() : string;
}