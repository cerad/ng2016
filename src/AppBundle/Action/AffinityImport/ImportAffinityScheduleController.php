<?php
namespace AppBundle\Action\Game\ImportAffinitySchedule;

use AppBundle\Action\AbstractController2;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class ImportAffinityScheduleController extends AbstractController2
{
    private $form;
    private $reader;
    private $updater;
    
    public function __construct(
        ImportAffinityScheduleForm $form,
        ImportAffinityScheduleReaderExcel $reader,
        ImportAffinityScheduleUpdater     $updater
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
            
            $this->reader->read($file->getRealPath());
            
            $commit = $formData['op'] === 'update' ? true : false;

            $teams = $this->reader->getRegTeams();
            $results = $this->updater->updateRegTeams($teams,$commit,$file->getClientOriginalName());
            $request->attributes->set('teamResults',$results);

            $pools = $this->reader->getPoolTeams();
            $results = $this->updater->updatePoolTeams($pools,$commit,$file->getClientOriginalName());
            $request->attributes->set('poolResults',$results);

            $games = $this->reader->getGames();
            $results = $this->updater->updateGames($games,$commit,$file->getClientOriginalName());
            $request->attributes->set('gameResults',$results);

        }
        return null;
    }
}