<?php
namespace AppBundle\Action\Game\Export;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Game\GameFinder;
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
            'division'  => 'B14U',
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
            'wantTeams'  => true,
        ];
        if ($searchData['division']) {
            $criteria['divisions'] = [$searchData['division']];
        }
        if ($searchData['program']) {
            $criteria['programs'] = [$searchData['program']];
        }
        $games = $this->finder->findGames($criteria);
        $request->attributes->set('games',$games);
        if(isset($criteria['programs'])) {
            $request->attributes->set('program', $criteria['programs']);
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
