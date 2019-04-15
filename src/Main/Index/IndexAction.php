<?php declare(strict_types=1);

namespace Zayso\Main\Index;

use Symfony\Component\HttpFoundation\Request;
use Zayso\Common\Contract\ActionInterface;
use Zayso\Common\Traits\AuthenticationTrait;
use Zayso\Common\Traits\RouterTrait;

class IndexAction implements ActionInterface
{
    use RouterTrait;
    use AuthenticationTrait;

    public function __invoke(Request $request)
    {
        // Just a simple redirect based on login
        return $this->getUser() ? $this->redirectToRoute('app_home') : $this->redirectToRoute('app_welcome');
    }
}
