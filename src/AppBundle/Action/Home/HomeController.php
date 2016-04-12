<?php

namespace AppBundle\Action\Home;

use AppBundle\Action\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class HomeController extends AbstractController
{
    
    public function __invoke(Request $request)
    {
        // Make sure signed in
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_welcome');
        }

        return null;

    }
}
