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
