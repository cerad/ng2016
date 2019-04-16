<?php declare(strict_types=1);

namespace Zayso\Project;

use Zayso\Common\AppVersion;
use Zayso\Common\AppVersionTrait;
use Zayso\Common\Traits\AuthenticationTrait;
use Zayso\Common\Traits\AuthorizationTrait;
use Zayso\Common\Traits\EscapeTrait;
use Zayso\Common\Traits\RouterTrait;

abstract class AbstractPageTemplate implements ProjectServiceInterface
{
    use EscapeTrait;
    use RouterTrait;
    use AuthorizationTrait;
    use AuthenticationTrait;

    protected $currentProject;
    protected $appVersion;

    public function __construct(
        CurrentProject $currentProject,
        AppVersion     $appVersion)
    {
        $this->currentProject = $currentProject;
        $this->appVersion     = $appVersion;
    }

    abstract public function render(string $content) : string;
}