<?php

namespace AppBundle\Action\Welcome;

use Cerad\Bundle\UserBundle\Action\Login\LoginForm;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WelcomeController extends Controller
{
    /** @var LoginForm  */
    private $loginForm;

    /** @var WelcomePageTemplate  */
    private $pageTemplate;

    public function __construct(WelcomePageTemplate $pageTemplate, LoginForm $loginForm)
    {
        $this->loginForm    = $loginForm;
        $this->pageTemplate = $pageTemplate;
    }
    public function __invoke(Request $request)
    {
        // Verify not signed in
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('app_welcome');
        }
        $params = [
            'base_dir'  => realpath($this->getParameter('kernel.root_dir').'/..'),
            'loginForm' => $this->loginForm,
        ];
        return new Response($this->pageTemplate->render($params));
    }
}
