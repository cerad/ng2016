<?php
namespace AppBundle\Action\App\Welcome;

use AppBundle\Action\AbstractController;

use Symfony\Component\HttpFoundation\Request;

class WelcomeController extends AbstractController
{
    public function __invoke(Request $request)
    {
        // Verify not signed in
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_home');
        }
        
        $request->attributes->set('base_dir',$this->getParameter('kernel.root_dir').'/..');
        
        return null;
    }
}
