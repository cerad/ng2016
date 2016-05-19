<?php
namespace AppBundle\Action\Game\Listing;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Game\GameFinder;
use AppBundle\Action\Results2016\ResultsFinder;
use Symfony\Component\HttpFoundation\Request;

class GameListingController extends AbstractController2
{
    private $finder;
    private $searchForm;

    private $projects;
    private $projectChoices;

    public function __construct(
        GameListingSearchForm  $searchForm,
        GameFinder             $finder,
        array $projectChoices,
        array $projects
    ) {
        $this->finder = $finder;
        $this->searchForm = $searchForm;

        $this->projects       = $projects;
        $this->projectChoices = $projectChoices;
    }
    public function __invoke(Request $request)
    {
        // Support multiple projects
        $projectId = $this->getDefaultProjectId();
        $searchData = [
            'projectId' => $projectId,
            'program'   => $this->getDefaultProgramForProject($projectId),
            'division'  => 'U14B',
            'show'      => 'all',
        ];
        // Override from session
        $session = $request->getSession();
        $sessionKey = 'game_listing';
        if ($session->has($sessionKey)) {
            $searchData = array_replace($searchData,$session->get($sessionKey));
        }
        // The form
        $this->searchForm->setData($searchData);
        $this->searchForm->handleRequest($request);
        if ($this->searchForm->isValid()) {
            // Need a better way for this nonsense
            $searchDataNew = $this->searchForm->getData();
            if ($searchDataNew['projectId'] !== $searchData['projectId']) {
                $projectId = $searchDataNew['projectId'];
                $searchDataNew['program'] = $this->getDefaultProgramForProject($projectId);
            }
            $session->set($sessionKey,$searchDataNew);
            return $this->redirectToRoute($this->getCurrentRouteName());
        }
        $criteria = [
            'projectIds' => [$searchData['projectId']],
            'programs'   => [$searchData['program']],
            'divisions'  => [$searchData['division']],
            'wantTeams'  => true,
        ];
        switch($searchData['show']) {
            case 'all':
                $regTeams = $this->finder->findRegTeams($criteria);
                $request->attributes->set('regTeams',$regTeams);

                $poolTeams = $this->finder->findPoolTeams($criteria);
                $request->attributes->set('poolTeams',$poolTeams);

                $games = $this->finder->findGames($criteria);
                $request->attributes->set('games',$games);
                break;
            
            case 'regTeams':
                $regTeams = $this->finder->findRegTeams($criteria);
                $request->attributes->set('regTeams',$regTeams);
                break;

            case 'poolTeams':
                $poolTeams = $this->finder->findPoolTeams($criteria);
                $request->attributes->set('poolTeams',$poolTeams);
                break;

            case 'games':
                $games = $this->finder->findGames($criteria);
                $request->attributes->set('games',$games);
                break;
        }
        return null;
    }
    private function getDefaultProjectId()
    {
        return array_keys($this->projectChoices)[0];
    }
    private function getDefaultProgramForProject($projectId)
    {
        return  array_keys($this->projects[$projectId]['programs'])[0];
    }
}
