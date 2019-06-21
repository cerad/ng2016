<?php
namespace AppBundle\Action\GameOfficial\AssignByAssignee;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Game\GameFinder;
use AppBundle\Action\GameOfficial\GameOfficialUpdater;
use AppBundle\Action\GameReport\GameReport;
use AppBundle\Action\GameReport\GameReportRepository;
use AppBundle\Action\GameReport\GameReportPointsCalculator;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class AssigneeController extends AbstractController2
{
    private $form;
    private $gameFinder;
    private $gameOfficialUpdater;

    public function __construct(
        AssigneeForm        $form,
        GameFinder          $gameFinder,
        GameOfficialUpdater $gameOfficialUpdater
    ) {
        $this->form       = $form;
        $this->gameFinder = $gameFinder;
        $this->gameOfficialUpdater = $gameOfficialUpdater;
    }
    public function __invoke(Request $request, $projectId, $gameNumber, $slot)
    {
        // Just to get it out of the way
        $redirect =  $this->redirectToRoute(
            $this->getCurrentRouteName(),
            ['projectId' => $projectId, 'gameNumber' => $gameNumber, 'slot' => $slot]
        );

        // Stash the back link in the session
        $session = $request->getSession();
        $sessionKey = 'game_report_update_back';
        if ($request->query->has('back')) {
            $session->set($sessionKey,$request->query->get('back'));
            return $redirect;
        }
        $backRouteName = 'schedule_official_2019'; // Inject or results link
        if ($session->has($sessionKey)) {
            $backRouteName = $session->get($sessionKey);
        }

        $game = $this->gameFinder->findGame($projectId,$gameNumber);
        if (!$game) {
            return $this->redirectToRoute($backRouteName);
        }
        // The form will clone this, should it?
        $gameOfficialOriginal = $game->getOfficial($slot);
        
        $form = $this->form;
        $form->setGame($game);

        $form->setSelfAssign($game->selfAssign);
        $form->setGameOfficial($gameOfficialOriginal);
        $form->setBackRouteName($backRouteName);
        $form->handleRequest($request);
        
        if ($form->isValid()) {

            $gameOfficial = $form->getGameOfficial();
            
            // Process some commands
            $assignState = $gameOfficial->assignState;
            switch($assignState) {
                case 'Declined':
                case 'RemoveByAssignee':
                    $gameOfficial->assignState = 'Open';
                    $gameOfficial->regPersonId = null;
                    break;
            }
            
            $this->gameOfficialUpdater->updateGameOfficial($gameOfficial,$gameOfficialOriginal);

            $backUrl = $this->generateUrl($backRouteName);
            $backUrl .= '#game-' . $game->gameId;

            return new RedirectResponse($backUrl);
            
            //return $redirect;
        }
        $request->attributes->set('game',$game);
        return null;
    }
}
