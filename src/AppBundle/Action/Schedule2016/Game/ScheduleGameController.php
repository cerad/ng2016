<?php

namespace AppBundle\Action\Schedule2016\Game;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Schedule2016\ScheduleFinder;

use Symfony\Component\HttpFoundation\Request;

class ScheduleGameController extends AbstractController2
{
    private $searchForm;
    private $scheduleFinder;

    private $projects;
    private $projectChoices;

    public function __construct(
        ScheduleGameSearchForm $searchForm,
        ScheduleFinder         $scheduleFinder,
        array $projectChoices,
        array $projects
    )
    {
        $this->searchForm     = $searchForm;
        $this->scheduleFinder = $scheduleFinder;

        $this->projects       = $projects;
        $this->projectChoices = $projectChoices;
    }
    public function __invoke(Request $request)
    {
        // First project in list
        $projectKey = array_keys($this->projectChoices)[0];

        // Second date in project
        $date = array_keys($this->projects[$projectKey]['dates'])[1];

        $searchData = [
            'projectKey' => $projectKey,
            'programs'   => ['Core'],
            'genders'    => ['G'],
            'ages'       => ['U14'],
            'dates'      => [$date],
            'sortBy'     => 1,
        ];
        // Save selected teams in session
        $session    = $request->getSession();
        $sessionKey = 'schedule_game_search_data_2016';

        if ($request->query->has('reset')) {
            $session->remove($sessionKey);
        }
        if ($session->has($sessionKey)) {
            $searchData = array_replace($searchData,$session->get($sessionKey));
        }
        $searchForm = $this->searchForm;
        $searchForm->setData($searchData);
        $searchForm->handleRequest($request);
        
        if ($searchForm->isValid()) {

            $searchDataNew = $searchForm->getData();
            if ($searchData['projectKey'] !== $searchDataNew['projectKey']) {

                // Getting way too tricky here but match dates by dow
                $dates = $this->projects[$searchData['projectKey']]['dates'];
                $dows = [];
                foreach($searchDataNew['dates'] as $date) {
                    $dows[] = $dates[$date];
                }
                $dates = $this->projects[$searchDataNew['projectKey']]['dates'];
                $datesNew = [];
                foreach($dates as $date => $dow) {
                    if (in_array($dow,$dows)) {
                        $datesNew[] = $date;
                    }
                }
                // Suppose something similiar for Core could be done as well but bored now
                $searchDataNew = array_replace($searchDataNew,[
                    'programs' => ['Core'],
                    'dates'    => $datesNew,
                ]);
            }
            $session->set($sessionKey,$searchDataNew);

            return $this->redirectToRoute($request->attributes->get('_route'));
        }
        // For now, restrict to one project
        $searchData['projectKeys'] = [$searchData['projectKey']];

        $games = $this->scheduleFinder->findGames($searchData,true);

        $request->attributes->set('games', $games);
        return null;
    }
}