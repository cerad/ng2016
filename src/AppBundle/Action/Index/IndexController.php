<?php

namespace AppBundle\Action\Index;

use Cerad\Bundle\UserBundle\Action\Login\LoginForm;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends Controller
{
    private $loginForm;
    private $pageTemplate;

    public function __construct(LoginForm $loginForm, IndexPageTemplate $pageTemplate)
    {
        $this->loginForm    = $loginForm;
        $this->pageTemplate = $pageTemplate;
    }
    public function __invoke(Request $request)
    {
        $params = [
            'base_dir'  => realpath($this->getParameter('kernel.root_dir').'/..'),
            'loginForm' => $this->loginForm,
        ];
        return new Response($this->pageTemplate->render($params));
    }
}
