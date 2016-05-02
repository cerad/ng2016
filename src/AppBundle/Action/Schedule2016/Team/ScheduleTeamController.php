<?php
namespace AppBundle\Action\Schedule2016\Team;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Project\Person\ProjectPersonRepositoryV2;

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
        $projectKey = array_keys($this->projectChoices)[0];

        $searchData = [
            'projectKey' => $projectKey,
            'program'    => 'Core',
            'name'       =>  null,
            'teams'      => [],
            'sortBy'     => 1,
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
            if ($searchData['projectKey'] !== $searchDataNew['projectKey']) {
                $searchDataNew['program'] = 'Core';
                $searchDataNew['teams']   = [];
            }
            if ($searchData['program'] !== $searchDataNew['program']) {
                $searchDataNew['teams'] = [];
            }
            $session->set($sessionKey,$searchDataNew);

            return $this->redirectToRoute($request->attributes->get('_route'));
        }
        if (!count($searchData['teams'])) {
            $request->attributes->set('games',[]);
            return null;
        }
        $criteria = [
            //'projectKeys' => [$searchData['projectKey']],
            //'programs'    => [$searchData['program'   ]],
            'projectTeamIds' => $searchData['teams'],
        ];
        
        $games = $this->scheduleFinder->findGames($criteria,true);
        
        $request->attributes->set('games',$games);
        
        return null;
    }
}
