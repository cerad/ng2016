<?php declare(strict_types=1);

namespace Zayso\Main\Welcome;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zayso\Common\Contract\ActionInterface;
use Zayso\Common\Traits\AuthorizationTrait;
use Zayso\Common\Traits\RouterTrait;
use Zayso\Project\CurrentProject;

class WelcomeAction implements ActionInterface
{
    use RouterTrait;
    use AuthorizationTrait;

    private $currentProject;

    public function __construct(CurrentProject $currentProject)
    {
        $this->currentProject = $currentProject;
    }
    public function __invoke(Request $request)
    {
        // Verify not signed in
        if ($this->isGranted('ROLE_USER')) {

//            Request::setTrustedProxies(['~']);

            return $this->redirectToRoute('app_home');
        }

        return new Response($this->currentProject->welcomeTemplate->render());
    }
}