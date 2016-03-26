<?php

namespace AppBundle\Action\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller
{
    /** @var AdminPageTemplate  */
    private $pageTemplate;

    public function __construct(AdminPageTemplate $pageTemplate)
    {
        $this->pageTemplate = $pageTemplate;
    }
    
    public function __invoke(Request $request)
    {
        // Verify user is admin
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_home');
        }
        
        $params = [];
        return new Response($this->pageTemplate->render($params));
    }
}
