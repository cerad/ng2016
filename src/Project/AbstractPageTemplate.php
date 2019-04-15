<?php declare(strict_types=1);

namespace Zayso\Project;

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
    use CurrentProjectTrait;
    use AppVersionTrait;

    abstract public function render(string $content) : string;
}