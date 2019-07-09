<?php
namespace AppBundle\Action\App\Welcome;

use AppBundle\Action\AbstractController;

use Symfony\Component\HttpFoundation\Request;

class WelcomeController extends AbstractController
{
    private $showThankYou;

    public function __construct(bool $showThankYou)
    {
        $this->showThankYou = $showThankYou;
    }

    public function __invoke(Request $request)
    {
        if($this->showThankYou) {
            return $this->redirectToRoute('app_tnx');
        }

        // Verify not signed in
        if ($this->isGranted('ROLE_USER')) {

//            Request::setTrustedProxies(['~']);

            return $this->redirectToRoute('app_home');
        }

        return null;
    }
}
