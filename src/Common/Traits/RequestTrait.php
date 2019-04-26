<?php declare(strict_types=1);

namespace Zayso\Common\Traits;

use Symfony\Component\HttpFoundation\RequestStack;

trait RequestTrait
{
    /** @var RequestStack */
    protected $requestStack;

    /** @required */
    public function injectRequestStack(RequestStack $requestStack) : void
    {
        $this->requestStack = $this->requestStack ?: $requestStack;
    }
    protected function getCurrentRouteName() : string
    {
        $request = $this->requestStack->getMasterRequest();
        return $request->attributes->get('_route');
    }
}