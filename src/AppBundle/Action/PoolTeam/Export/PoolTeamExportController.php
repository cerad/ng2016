<?php
namespace AppBundle\Action\PoolTeam\Export;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Game\GameFinder;
use Symfony\Component\HttpFoundation\Request;

/* ==============================================
 * Not too sure about this design but this
 * is linked to the games listing and just dumps whatever
 * games are currently selected
 */
class PoolTeamExportController extends AbstractController2
{
    private $finder;

    public function __construct(
        GameFinder             $finder
    ) {
        $this->finder = $finder;
    }
    public function __invoke(Request $request)
    {
        // Override from session
        $session = $request->getSession();
        $sessionKey = 'game_listing';
        if (!$session->has($sessionKey)) {
            return $this->redirectToRoute('game_listing');
        }
        $searchData = $session->get($sessionKey);
        
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
        $poolTeams = $this->finder->findPoolTeams($criteria);
        $request->attributes->set('poolTeams',$poolTeams);
        if(isset($criteria['programs'])) {
            $request->attributes->set('program', $criteria['programs']);
        }

        return null;
    }
}
