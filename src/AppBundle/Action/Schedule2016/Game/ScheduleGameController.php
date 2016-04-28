<?php

namespace AppBundle\Action\Schedule2016\Game;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Schedule2016\ScheduleFinder;

use AppBundle\Action\Schedule2016\ScheduleGame;
use Symfony\Component\HttpFoundation\Request;

class ScheduleGameController extends AbstractController2
{
    private $finder;

    public function __construct(ScheduleFinder $finder)
    {
        $this->finder = $finder;
    }
    public function __invoke(Request $request)
    {
        $project = $this->getCurrentProjectInfo();

        // Save selected teams in session
        $session = $request->getSession();
        $search = $session->has('schedule_game_search') ? $session->get('schedule_game_search') : [];

        $search = array_merge($project['search_defaults'],$search);

        // Search posted
        if ($request->isMethod('POST')) {
            $search = $request->request->get('search');
            $session->set('schedule_game_search',$search);
            return $this->redirectToRoute('schedule_game_2016');  // TODO add search parameters?
        }

        $games = $this->finder->findGames($search,true);

        usort($games,function(ScheduleGame $game1, ScheduleGame $game2) {

            if ($game1->start > $game2->start) return  1;
            if ($game1->start < $game2->start) return -1;

            if ($game1->poolView > $game2->poolView) return  1;
            if ($game1->poolView < $game2->poolView) return -1;
            
            return 0;
        });
        $request->attributes->set('games', $games);
        $request->attributes->set('search',$search);
     
        return null;
    }
}