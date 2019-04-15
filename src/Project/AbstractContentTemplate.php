<?php declare(strict_types=1);

namespace Zayso\Project;

use Zayso\Common\Traits\AuthenticationTrait;
use Zayso\Common\Traits\AuthorizationTrait;
use Zayso\Common\Traits\EscapeTrait;
use Zayso\Common\Traits\RouterTrait;

abstract class AbstractContentTemplate implements ProjectServiceInterface
{
    use EscapeTrait;
    use RouterTrait;
    use CurrentProjectTrait;

    abstract public function render() : string;
}