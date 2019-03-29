<?php
namespace AppBundle\Action\Schedule2019\Assignor;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Game\Game;
use AppBundle\Action\Schedule2019\ScheduleControllerTrait;
use AppBundle\Action\Schedule2019\ScheduleFinder;

use Symfony\Component\HttpFoundation\Request;

class ScheduleAssignorController extends AbstractController2
{
    use ScheduleControllerTrait;

    private $searchForm;
    private $scheduleFinder;

    private $projects;
    private $projectChoices;
    
    private $reportKey;

    public function __construct(
        ScheduleAssignorSearchForm $searchForm,
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

        // Save selected teams in session
        $searchData = [
            'projectId'  => $projectId,
            'programs'   => ['Core'],
            'genders'    => ['G'],
            'ages'       => ['U14'],
            'dates'      => [$date],
            'sortBy'     => 1,
            'filter'     => null,
            'reportKey'  => null   
        ];

        $session = $request->getSession();
        $sessionKey = 'schedule_assignor_search_data_2016';

        if ($session->has($sessionKey)) {
            $searchData = array_merge($searchData,$session->get($sessionKey));
        };

        //if ($request->query->has('reset')) {
        //    $session->remove($sessionKey);
        //}
        //if ($session->has($sessionKey)) {
        //    $searchData = array_replace($searchData,$session->get($sessionKey));
        //}
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
        
        $games = $this->scheduleFinder->findGames($searchData,true);

        $games = $this->filterGames($games,$searchData['filter']);
        
        //apply report filters on assignState
        $this->reportKey = $searchData['reportKey'];
        $games = $this->filterGamesForReport($games);
        
        $request->attributes->set('games',  $games);
        $request->attributes->set('filter', $searchData['filter']);

        return null;
    }

    /**
     * @param Game[] $games
     * @return array
     */
    private function filterGamesForReport($games)
    {
        //from assign.yml
          //Open:      Open # Open
          //Requested: Req
          //If_Needed: If_N
          //Accepted:  Acc
          //Declined:  Dec
          //Approved:  App
          //Rejected:  Rej
          //Reviewing: Rev
          //Pending:   Pend
          //Published: Pub
          //Notified:  Not
  
        if (is_null($games)) return $games;
   
        if (in_array($this->reportKey, array(null, 'All'))) return $games;

        $gamesReport = [];
        $reportKey = strtolower($this->reportKey);
        foreach($games as $game) {
            $officials = $game->getOfficials();
            $next = false;
            foreach($officials as $official) {
                $assignState = strtolower($official->assignState);
                switch ($reportKey) {
                    case 'issues':
                        if ($assignState != "accepted" && $assignState != 'approved') {
                            if (!in_array($game,$gamesReport)) $gamesReport[] = $game;
                        }
                        continue;
                    case 'open':
                        if ($assignState == "open") {
                            if (!in_array($game,$gamesReport)) $gamesReport[] = $game;
                        }
                        continue;
                    case 'pending':
                        if ($assignState == "pending") {
                            if (!in_array($game,$gamesReport)) $gamesReport[] = $game;
                        }
                        continue;
                    case 'published':
                        if ($assignState == "published") {
                            if (!in_array($game,$gamesReport)) $gamesReport[] = $game;
                        }
                        continue;
                    case 'requested':
                        if ($assignState == "requested") {
                            if (!in_array($game,$gamesReport)) $gamesReport[] = $game;
                        }
                        continue;
                    case 'turnback':
                        if ($assignState == "turnback") {            
                            if (!in_array($game,$gamesReport)) $gamesReport[] = $game;
                        }
                        continue;
                }
            }
        }
        
        return $gamesReport;

    }
}
