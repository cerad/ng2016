<?php
namespace AppBundle\Action\RegTeam\Transform;

use AppBundle\Action\AbstractController2;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class RegTeamTransformController extends AbstractController2
{
    private $form;
    private $reader;

    public function __construct(
        RegTeamTransformForm $form,
        RegTeamTransformReaderExcel $reader
    ) {
        $this->form    = $form;
        $this->reader  = $reader;
    }
    public function __invoke(Request $request)
    {
        $formData = [
            'sheet' => 'U10G',
            'file'  => null,
        ];
        $this->form->setData($formData);
        $this->form->handleRequest($request);
        if ($this->form->isValid()) {
            $formData = $this->form->getData();
            
            /** @var UploadedFile $file */
            $file  = $formData['file'];
            $sheet = $formData['sheet'];
            
            $regTeams = $this->reader->read($file->getRealPath(),$sheet);

            // Let the view deal with rendering
            $request->attributes->set('regTeams',$regTeams);
            $request->attributes->set('sheet',   $sheet);
            
            //dump($regTeams);
            
        }
        return null;
    }
}