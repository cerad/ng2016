<?php

namespace AppBundle\Action\Index;

use Cerad\Bundle\UserBundle\Action\Login\LoginForm;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends Controller
{
    private $authUtils;
    private $loginForm;

    public function __construct(LoginForm $loginForm)
    {
        $this->loginForm = $loginForm;
    }
    public function __invoke(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('@App/Index/Index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
            'loginForm' => $this->loginForm,
        ]);
    }
}
