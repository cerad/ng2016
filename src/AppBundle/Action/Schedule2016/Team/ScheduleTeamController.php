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
    
    public function __construct(
        ScheduleTeamSearchForm $searchForm,
        ScheduleFinder         $scheduleFinder
    )
    {
        $this->searchForm     = $searchForm;
        $this->scheduleFinder = $scheduleFinder;
    }
    public function __invoke(Request $request)
    {
        $searchData = [
            'projectKey' => $this->getCurrentProjectKey(),
            'program'    => 'Core',
            'name'       =>  null,
            'teams'      => [],
        ];
        $session = $request->getSession();
        if ($session->has('schedule_team_search_data_2016')) {
            $searchData = array_replace($searchData,$session->get('schedule_team_search_data_2016'));
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
            // TODO Some way to clear this
            $session->set('schedule_team_search_data_2016',$searchDataNew);

            return $this->redirectToRoute('schedule_team_2016');
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
