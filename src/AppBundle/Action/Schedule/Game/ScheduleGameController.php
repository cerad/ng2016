<?php

namespace AppBundle\Action\Schedule\Game;

use AppBundle\Action\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ScheduleGameController extends AbstractController
{
    public function __invoke(Request $request)
    {
        $project = $this->getCurrentProject()['info'];

        // Save selected teams in session
        $search = $project['search_defaults'];

        $session = $request->getSession();
        $search = $session->has('schedule_game_search') ? $session->get('schedule_game_search') : [];

        // Search posted
        if ($request->isMethod('POST')) {
            $search = $request->request->get('search');
            $session->set('schedule_game_search',$search);
            return $this->redirectToRoute('app_schedule_game');  // TODO add search parameters
        }

        $request->attributes->set('project',$project);
        $request->attributes->set('schedule_game_search',$search);
        
        return null;
    }
}