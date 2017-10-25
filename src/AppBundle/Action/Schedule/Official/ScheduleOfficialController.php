<?php

namespace AppBundle\Action\Schedule\Official;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Schedule\ScheduleControllerTrait;
use AppBundle\Action\Schedule\ScheduleFinder;

use AppBundle\Action\Schedule\ScheduleSearchForm;

use Symfony\Component\HttpFoundation\Request;

class ScheduleOfficialController extends AbstractController2
{
    use ScheduleControllerTrait;

    private $searchForm;
    private $scheduleFinder;

    private $projects;
    private $projectChoices;

    public function __construct(
        ScheduleSearchForm $searchForm,
        ScheduleFinder     $scheduleFinder,
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
        $projectId = array_keys($this->projectChoices)[0];

        // Second date in project
        $date = array_keys($this->projects[$projectId]['dates'])[1];

        $searchData = [
            'projectId' => $projectId,
            'programs'   => ['Core'],
            'genders'    => ['G'],
            'ages'       => ['U14'],
            'dates'      => [$date],
            'sortBy'     => 1,
            'filter'     => null,
        ];
        // Save selected teams in session
        $session    = $request->getSession();
        $sessionKey = 'schedule_official_search_data_2018';

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
            if ($searchData['projectId'] !== $searchDataNew['projectId']) {

                // Getting way too tricky here but match dates by dow
                $dates = $this->projects[$searchData['projectId']]['dates'];
                $dows = [];
                foreach($searchDataNew['dates'] as $date) {
                    $dows[] = $dates[$date];
                }
                $dates = $this->projects[$searchDataNew['projectId']]['dates'];
                $datesNew = [];
                foreach($dates as $date => $dow) {
                    if (in_array($dow,$dows)) {
                        $datesNew[] = $date;
                    }
                }
                $searchDataNew = array_replace($searchDataNew,[
                    'programs' => ['Core'],
                    'dates'    => $datesNew,
                ]);
            }
            $session->set($sessionKey,$searchDataNew);

            return $this->redirectToRoute($request->attributes->get('_route'));
        }
        // For now, restrict to one project
        $searchData['projectIds'] = [$searchData['projectId']];
        $searchData['wantOfficials'] = true;

        // Shows my games
        $searchData['regPersonId'] = $this->getUserRegPersonId();

        $games = $this->scheduleFinder->findGames($searchData,true);

        $games = $this->filterGames($games,$searchData['filter']);

        $request->attributes->set('games',  $games);
        $request->attributes->set('filter', $searchData['filter']);

        return null;
    }
}