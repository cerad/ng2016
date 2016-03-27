<?php

namespace AppBundle\Action\Admin;

use AppBundle\Action\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends AbstractController
{
    /** @var AdminPageTemplate  */
    private $pageTemplate;
    
    private $project;

    public function __construct(AdminPageTemplate $pageTemplate)
    {
        $this->pageTemplate = $pageTemplate;
    }
    
    public function __invoke(Request $request)
    {
        // Verify user is admin
        if (!$this->isGranted('ROLE_STAFF')) {
            return $this->redirectToRoute('app_home');
        }
        
        $this->project = $project = $this->getCurrentProject()['info'];
        
        $params = [
          'project' => $this->project,          
        ];
        
        return new Response($this->pageTemplate->render($params));
    }
 
}
