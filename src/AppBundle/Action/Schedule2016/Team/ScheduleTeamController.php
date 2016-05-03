<?php
namespace AppBundle\Action\Schedule2016\Team;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Schedule2016\ScheduleFinder;

use Symfony\Component\HttpFoundation\Request;

class ScheduleTeamController extends AbstractController2
{
    private $searchForm;
    private $scheduleFinder;
    
    private $projects;
    private $projectChoices;

    public function __construct(
        ScheduleTeamSearchForm $searchForm,
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
        $projectId = array_keys($this->projectChoices)[0];

        $searchData = [
            'projectId' => $projectId,
            'program'   => 'Core',
            'teamName'  =>  null,
            'regTeams'  => [],
            'sortBy'    => 1,
        ];
        $session = $request->getSession();
        $sessionKey = 'schedule_team_search_data_2016';

        if ($request->query->has('reset')) {
            $session->remove($sessionKey);
        }
        if ($session->has($sessionKey)) {
            $searchData = array_replace($searchData,$session->get($sessionKey));
        };
        $searchForm = $this->searchForm;
        $searchForm->setData($searchData);
        
        $searchForm->handleRequest($request);
        if ($searchForm->isValid()) {

            $searchDataNew = $searchForm->getData();
            if ($searchData['projectId'] !== $searchDataNew['projectId']) {
                $searchDataNew['program' ] = 'Core';
                $searchDataNew['regTeams'] = [];
            }
            if ($searchData['program'] !== $searchDataNew['program']) {
                $searchDataNew['regTeams'] = [];
            }
            $session->set($sessionKey,$searchDataNew);

            return $this->redirectToRoute($request->attributes->get('_route'));
        }
        if (!count($searchData['regTeams'])) {
            $request->attributes->set('games',[]);
            return null;
        }
        $criteria = [
            'regTeamIds' => $searchData['regTeams' ],
            'sortBy'     => $searchData['sortBy'],
        ];
        
        $games = $this->scheduleFinder->findGames($criteria,true);
        
        $request->attributes->set('games',$games);
        
        return null;
    }
}
