<?php declare(strict_types=1);

namespace Zayso\Common\Traits;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

trait RouterTrait
{
    /** @var RouterInterface */
    protected $router;

    /** @required */
    public function setOnceRouter(RouterInterface $router)
    {
        $this->router = $this->router ?: $router;
    }
    protected function generateUrl(
        string $route,
        array $parameters = array(),
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->router->generate($route, $parameters, $referenceType);
    }
    // Handy for mail templates
    protected function generateUrlAbsoluteUrl(string $route, array $parameters = array())
    {
        return $this->router->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }
    protected function redirect($url, $status = 302) : RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }
    protected function redirectToRoute($route, array $parameters = array(), $status = 302) : RedirectResponse
    {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }
}
