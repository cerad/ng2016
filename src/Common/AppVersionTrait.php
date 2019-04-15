<?php declare(strict_types=1);

namespace Zayso\Common;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

trait AppVersionTrait
{
    /** @var AppVersion */
    protected $appVersion;

    /** @required */
    public function setOnceVersion(AppVersion $appVersion)
    {
        $this->appVersion = $this->appVersion ?: $appVersion;
    }
}
