<?php

namespace AppBundle\Action\RegTeam;

use AppBundle\Action\AbstractController2;
use AppBundle\Action\Game\GameFinder;

use Symfony\Component\HttpFoundation\Request;

class RegTeamController extends AbstractController2
{
    /* @var GameFinder */
    private $finder;

    /*  @var RegTeamUploadForm */
    private $regTeamUploadForm;
    
    /*  @var ProjectChoices  */
    private $projectChoices = [];

    /*  @var Projects  */
    private $projects = [];

    private $divisionChoices = [
        'U10B' => 'U-10 Boys',
        'U10G' => 'U-10 Girls',
        'U12B' => 'U-12 Boys',
        'U12G' => 'U-12 Girls',
        'U14B' => 'U-14 Boys',
        'U14G' => 'U-14 Girls',
        'U16B' => 'U-16 Boys',
        'U16G' => 'U-16 Girls',
        'U19B' => 'U-19 Boys',
        'U19G' => 'U-19 Girls',
    ];
    
    public function __construct(
        GameFinder $finder,
        RegTeamUploadForm $regTeamUploadForm,
        array $projectChoices,
        array $projects
    )
    {
        $this->finder = $finder;
        $this->regTeamUploadForm = $regTeamUploadForm;
        $this->projectChoices = $projectChoices;
        $this->projects = $projects;
    }
    public function __invoke(Request $request)
    {
        $importForm = $this->regTeamUploadForm;
        $importForm->handleRequest($request);
        if ($importForm->isValid()) {
            
            //TODO: add data processing
            $msg = $importForm->renderMessages();            
            $request->attributes->set('importMessages',$msg);                

            return $this->redirectToRoute('game_listing');
        }
         
        // Support multiple projects: just following the leader
        $projectId = $this->getDefaultProjectId();
        $divisions = array_keys($this->divisionChoices);

        $criteria = [
            'projectIds' => [$projectId],
            'programs'   => [$this->getDefaultProgramForProject($projectId)],
            'wantTeams'  => true,
        ];
        
        $regTeams = [];
        foreach ($divisions as $division) {
            $criteria['divisions'] = [$division];

            $regTeams[$division] = $this->finder->findRegTeams($criteria);
        }

        $request->attributes->set('regTeamsByDivision',$regTeams);

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
