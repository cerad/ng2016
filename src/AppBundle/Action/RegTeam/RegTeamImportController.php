<?php

namespace AppBundle\Action\RegTeam;

use AppBundle\Action\AbstractController2;
use AppBundle\Action\Game\GameFinder;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RegTeamController extends AbstractController2
{
    /*  @var RegTeamUploadForm */
    private $regTeamUploadForm;
    
    /*  @var ProjectChoices  */
    private $projectChoices = [];

    /*  @var Projects  */
    private $projects = [];

    public function __construct(
        RegTeamUploadForm $regTeamUploadForm,
        array $projectChoices,
        array $projects
    )
    {
        $this->regTeamUploadForm = $regTeamUploadForm;
        $this->projectChoices = $projectChoices;
        $this->projects = $projects;
    }
    public function __invoke(Request $request)
    {
        $importForm = $this->regTeamUploadForm;
        $importForm->handleRequest($request);

        $params = $request->request->all();
        $isTest = $request->request->get('isTest');

        if ($importForm->isValid() and empty($isTest)) {
            
            //TODO: add data processing

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
