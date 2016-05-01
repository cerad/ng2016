<?php

namespace AppBundle\Action\Schedule2016\Game;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Schedule2016\ScheduleGame;
use AppBundle\Action\Schedule2016\ScheduleFinder;

use Symfony\Component\HttpFoundation\Request;

class ScheduleGameController extends AbstractController2
{
    private $finder;
    private $searchForm;
    
    public function __construct(
        ScheduleGameSearchForm $searchForm,
        ScheduleFinder $finder
    )
    {
        $this->finder = $finder;
        $this->searchForm = $searchForm;
    }
    public function __invoke(Request $request)
    {
        $searchData = [
            'projectKey' => $this->getCurrentProjectKey(),
            'programs'   => ['Core'],
            'genders'    => ['G'],
            'ages'       => ['U14'],
            'dates'      => ['2016-07-07'], // Pull from projects for current project
            'sortBy'     => 1,
        ];
        // Save selected teams in session
        $session = $request->getSession();
        if ($session->has('schedule_game_search_data_2016')) {
            $searchData = array_replace($searchData,$session->get('schedule_game_search_data_2016'));
        }
        $searchForm = $this->searchForm;
        $searchForm->setData($searchData);
        $searchForm->handleRequest($request);
        
        if ($searchForm->isValid()) {

            $searchDataNew = $searchForm->getData();
            if ($searchData['projectKey'] !== $searchDataNew['projectKey']) {
                $searchDataNew = array_replace($searchDataNew,[
                    'programs' => ['Core'], // Rest should be okay, could merge the dates but oh well
                ]);
            }
            // TODO Some way to clear this
            $session->set('schedule_game_search_data_2016',$searchDataNew);

            return $this->redirectToRoute('schedule_game_2016');
        }
        
        $games = $this->finder->findGames($searchData,true);

        $games = $this->sortGames($games,$searchData['sortBy']);

        $request->attributes->set('games', $games);
        return null;
    }
    // Move this to ScheduleSorter
    protected function sortGames($games,$sortBy)
    {
        if ($sortBy === 1) {
            usort($games,function(ScheduleGame $game1, ScheduleGame $game2) {

                if ($game1->start > $game2->start) return  1;
                if ($game1->start < $game2->start) return -1;

                if ($game1->poolView > $game2->poolView) return  1;
                if ($game1->poolView < $game2->poolView) return -1;

                if ($game1->fieldName > $game2->fieldName) return  1;
                if ($game1->fieldName < $game2->fieldName) return -1;

                return 0;
            });
            return $games;
        }
        if ($sortBy === 2) {
            usort($games,function(ScheduleGame $game1, ScheduleGame $game2) {

                $date1 = substr($game1->start,0,10);
                $date2 = substr($game1->start,0,10);
                if ($date1 > $date2) return  1;
                if ($date1 < $date2) return -1;

                if ($game1->fieldName > $game2->fieldName) return  1;
                if ($game1->fieldName < $game2->fieldName) return -1;

                $time1 = substr($game1->start,11); // 2016-07-07 08:00:00
                $time2 = substr($game2->start,11);
                if ($time1 > $time2) return  1;
                if ($time1 < $time2) return -1;

                return 0;
            });
            return $games;
        }
        return $games;
    }
}