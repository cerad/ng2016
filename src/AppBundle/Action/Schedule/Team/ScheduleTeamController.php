<?php

namespace AppBundle\Action\Schedule\Team;

use AppBundle\Action\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ScheduleTeamController extends AbstractController
{
    /** @var  ScheduleRepository */

    public function __construct()
    {
    }
    public function __invoke(Request $request)
    {
        // TODO: get team keys from request query parameters

        // Get selected teams from session
        $session = $request->getSession();
        $projectTeamKeys = $session->has('project_team_keys') ? $session->get('project_team_keys') : [];
        $projectTeamKeys = !empty($projectTeamKeys) ? $projectTeamKeys : array('0');
        
        // Search posted
        if ($request->isMethod('POST')) {
            $projectTeamKeys = $request->request->get('project_teams');
            $session->set('project_team_keys',$projectTeamKeys);
            return $this->redirectToRoute('app_schedule_team');  // TODO add search parameters
        }
        $request->attributes->set('projectTeamKeys',$projectTeamKeys);

        return null;
    }
}
