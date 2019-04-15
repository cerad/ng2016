<?php declare(strict_types=1);

namespace Zayso\Main\File;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Zayso\Common\Contract\ActionInterface;

/* ==============================================
 * The production site appears to block access to pdf files
 * Not sure how to fix it so use this to serve files
 * Could be expanded later for different types of files
 */
class FilePdfAction implements ActionInterface
{
    public function __invoke(Request $request, $fileName)
    {
        $response = new Response();

        define('PROJECT_ROOT', realpath(__DIR__ . '/../../../../..'));

        $filePath = PROJECT_ROOT . '/web/pdf/' . $fileName;

        if (!file_exists($filePath)) {
            throw new AccessDeniedException('Files is not accessible');
        }
        $response->setContent(file_get_contents($filePath));

        $response->headers->set('Content-Type', 'application/pdf');

        $response->headers->set('Content-Disposition', 'inline; filename='. $fileName);
        
        return $response;
    }
}
