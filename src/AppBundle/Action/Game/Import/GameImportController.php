<?php
namespace AppBundle\Action\Game\Import;

use AppBundle\Action\AbstractController2;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class GameImportController extends AbstractController2
{
    private $form;
    private $reader;
    private $updater;
    
    public function __construct(
        GameImportForm $form,
        GameImportReaderExcel $reader,
        GameImportUpdater     $updater
    ) {
        $this->form    = $form;
        $this->reader  = $reader;
        $this->updater = $updater;
    }
    public function __invoke(Request $request)
    {
        $formData = [
            'op'   => 'verify',
            'file' => null,
        ];
        $this->form->setData($formData);
        $this->form->handleRequest($request);
        if ($this->form->isValid()) {
            $formData = $this->form->getData();
            
            /** @var UploadedFile $file */
            $file = $formData['file'];
            
            $games = $this->reader->read($file->getRealPath());
            
            $commit = $formData['op'] === 'update' ? true : false;
            
            $results = $this->updater->updateGames($games,$commit);
            
            $results->fileName = $file->getClientOriginalName();
            
            $request->attributes->set('results',$results);
        }
        return null;
    }
}