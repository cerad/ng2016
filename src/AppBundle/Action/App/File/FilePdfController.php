<?php

namespace AppBundle\Action\App\File;

use AppBundle\Action\AbstractController2;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/* ==============================================
 * The production site appears to block access to pdf files
 * Not sure how to fix it so use this to serve files
 * Could be expanded later for different types of files
 */
class FilePdfController extends AbstractController2
{
    public function __invoke(Request $request, $fileName)
    {
        $response = new Response();

        $filePath = $this->container->getParameter('kernel.root_dir').'/../web/pdf/' . $fileName;

        if (!file_exists($filePath)) {
            throw new AccessDeniedException('Files is not accessible');
        }
        $response->setContent(file_get_contents($filePath));

        $response->headers->set('Content-Type', 'application/pdf');

        $response->headers->set('Content-Disposition', 'inline; filename='. $fileName);
        
        return $response;
    }
}
