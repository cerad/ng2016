<?php

namespace AppBundle\Action\RegTeam;

use AppBundle\Action\AbstractController2;
use AppBundle\Action\Game\GameFinder;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RegTeamExportController extends AbstractController2
{
    /* @var GameFinder */
    private $finder;

    /*  @var ProjectChoices  */
    private $projectChoices = [];

    /*  @var Projects  */
    private $projects = [];

    private $divisionChoices = [
        'B10U' => '10UU Boys',
        'G10U' => '10UU Girls',
        'B12U' => '12U Boys',
        'G12U' => '12U Girls',
        'B14U' => '14U Boys',
        'G14U' => '14U Girls',
        'B16U' => '16U Boys',
        'G16U' => '16U Girls',
        'B19U' => '19U Boys',
        'G19U' => '19U Girls',
    ];
    
    public function __construct(
        GameFinder $finder,
        array $projectChoices,
        array $projects
    )
    {
        $this->finder = $finder;
        $this->projectChoices = $projectChoices;
        $this->projects = $projects;
    }
    public function __invoke(Request $request)
    {

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
        return array_keys($this->projectChoices)[0];
    }
    private function getDefaultProgramForProject($projectId)
    {
        return  array_keys($this->projects[$projectId]['programs'])[0];
    }
}
