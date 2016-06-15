<?php

namespace AppBundle\Action\RegTeam;

use AppBundle\Action\AbstractController2;
use AppBundle\Action\Game\GameFinder;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RegTeamImportController extends AbstractController2
{
    /*  @var RegTeamUploadForm */
    private $regTeamUploadForm;
    
    /*  @var ProjectChoices  */
    private $projectChoices = [];

    /*  @var Projects  */
    private $projects = [];

    /*  @var Projects  */
    private $regTeamUpdater;

    public function __construct(
        RegTeamUploadForm $regTeamUploadForm,
        array $projectChoices,
        array $projects,
        RegTeamUpdater $regTeamUpdater
    )
    {
        $this->regTeamUploadForm = $regTeamUploadForm;
        $this->projectChoices = $projectChoices;
        $this->projects = $projects;
        $this->regTeamUpdater = $regTeamUpdater;
    }
    public function __invoke(Request $request)
    {
        $importForm = $this->regTeamUploadForm;
        $importForm->handleRequest($request);

        $isTest = $request->attributes->get('isTest');

        if ($importForm->isValid() and empty($isTest)) {            
            $data = $importForm->getData();
            
            //TODO: add data storage
        }

        return null;
    }
    private function getDefaultProjectId()
    {
        return array_keys($this->projectChoices)[1];
    }
    private function getDefaultProgramForProject($projectId)
    {
        return  array_keys($this->projects[$projectId]['programs'])[0];
    }
}
