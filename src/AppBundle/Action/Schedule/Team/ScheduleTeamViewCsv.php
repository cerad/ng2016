<?php

namespace AppBundle\Action\Schedule\Team;

use AppBundle\Action\AbstractView;

use AppBundle\Action\Schedule\ScheduleRepository;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ScheduleTeamViewCsv extends AbstractView
{
    /** @var  ScheduleRepository */
    private $scheduleRepository;

    public function __construct(ScheduleRepository $scheduleRepository)
    {
        $this->scheduleRepository = $scheduleRepository;
    }
    public function __invoke(Request $request)
    {
        $projectTeamKeys = $request->attributes->get('projectTeamKeys');

        // Find games
        $projectGames = $this->scheduleRepository->findProjectGamesForProjectTeamKeys($projectTeamKeys);

        return new Response('Generate CSV File ' . count($projectGames));
    }
}
