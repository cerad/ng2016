<?php
namespace AppBundle\Action\GameOfficial\AssignByAssignor;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Game\GameFinder;
use AppBundle\Action\GameOfficial\GameOfficialUpdater;

use Symfony\Component\HttpFoundation\Request;

class AssignorController extends AbstractController2
{
    private $form;
    private $gameFinder;
    private $gameOfficialUpdater;
    
    public function __construct(
        AssignorForm        $form,
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
        $backRouteName = 'schedule_official_2016'; // Inject or results link
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
        $form->setGameOfficial($gameOfficialOriginal);
        $form->setBackRouteName($backRouteName);
        $form->handleRequest($request);
        
        if ($form->isValid()) {

            $gameOfficial = $form->getGameOfficial();

            // TODO Move the logic for processing state changes somewhere?
            if ($gameOfficial->assignState === 'RemoveByAssignee') {
                $gameOfficial->assignState = 'Open';
                $gameOfficial->regPersonId = null;
            }
            $this->gameOfficialUpdater->updateGameOfficial($gameOfficial,$gameOfficialOriginal);
            
            return $redirect;
        }
        $request->attributes->set('game',$game);
        return null;
    }
}