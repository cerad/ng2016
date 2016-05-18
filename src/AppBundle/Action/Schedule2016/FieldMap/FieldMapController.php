<?php
namespace AppBundle\Action\Schedule2016\FieldMap;

use AppBundle\Action\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FieldMapController extends AbstractController
{
    public function __invoke(Request $request)
    {
        //get content
        $filename = $request->attributes->get('filename');
        
        $path = $this->get('kernel')->getRootDir(). "/../web/pdf/";
        
        $content = file_get_contents($path.$filename);
        
        // Generate response
        $response = new Response();
        
        // Set headers
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '";');
        
        $response->setContent($content);
    
        return $response;
    }
}