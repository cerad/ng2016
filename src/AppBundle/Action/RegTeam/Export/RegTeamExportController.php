<?php
namespace AppBundle\Action\RegTeam\Export;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\RegTeam\RegTeamFinder;
use Symfony\Component\HttpFoundation\Request;

/* ==============================================
 * Not too sure about this design but this
 * is linked to the games listing and just dumps whatever
 * games are currently selected
 */
class RegTeamExportController extends AbstractController2
{
    private $finder;

    public function __construct(
        RegTeamFinder $finder
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
        $regTeams = $this->finder->findRegTeams($criteria);
        $request->attributes->set('regTeams',$regTeams);
        if(isset($criteria['programs'])) {
            $request->attributes->set('program', $criteria['programs']);
        }

        return null;
    }
}
