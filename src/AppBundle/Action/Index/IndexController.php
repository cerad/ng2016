<?php

namespace AppBundle\Action\Index;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        // Just a simple redirect based on login
        return $this->getUser() ? $this->redirectToRoute('app_home') : $this->redirectToRoute('app_welcome');
    }
}
