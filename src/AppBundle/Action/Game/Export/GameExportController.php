<?php
namespace AppBundle\Action\Game\Export;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Game\GameFinder;
use AppBundle\Action\Results2016\ResultsFinder;
use Symfony\Component\HttpFoundation\Request;

/* ==============================================
 * Not too sure about this design but this
 * is linked to the games listing and just dumps whatever
 * games are currently selected
 */
class GameExportController extends AbstractController2
{
    private $finder;
    
    private $projects;
    private $projectChoices;

    public function __construct(
        GameFinder             $finder,
        array $projectChoices,
        array $projects
    ) {
        $this->finder = $finder;

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
        $criteria = [
            'projectIds' => [$searchData['projectId']],
            'programs'   => [$searchData['program']],
            'divisions'  => [$searchData['division']],
            'wantTeams'  => true,
        ];
        $games = $this->finder->findGames($criteria);
        $request->attributes->set('games',$games);
        
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
