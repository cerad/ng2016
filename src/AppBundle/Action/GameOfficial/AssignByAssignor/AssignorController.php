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
        AssignorForm $form,
        GameFinder $gameFinder,
        GameOfficialUpdater $gameOfficialUpdater
    ) {
        $this->form = $form;
        $this->gameFinder = $gameFinder;
        $this->gameOfficialUpdater = $gameOfficialUpdater;
    }

    public function __invoke(Request $request, $projectId, $gameNumber, $slot)
    {
        // Just to get it out of the way
        $currentRouteName = $this->getCurrentRouteName();
        $redirect = $this->redirectToRoute(
            $currentRouteName,
            ['projectId' => $projectId, 'gameNumber' => $gameNumber, 'slot' => $slot]
        );

        // Stash the back link in the session
        $session = $request->getSession();
        $sessionKey = $currentRouteName;
        if ($request->query->has('back')) {
            $session->set($sessionKey, $request->query->get('back'));

            return $redirect;
        }
        $backRouteName = 'schedule_assignor_2018'; // Inject or results link
        if ($session->has($sessionKey)) {
            $backRouteName = $session->get($sessionKey);
        }
        $game = $this->gameFinder->findGame($projectId, $gameNumber);
        if (!$game) {
            return $this->redirectToRoute($backRouteName);
        }
        $gameOfficialsOriginal = $game->getOfficials();

        $form = $this->form;
        $form->setGame($game);
        $form->setBackRouteName($backRouteName);
        $form->handleRequest($request);

        $conflicts = $form->getErrors();
        $gameOfficials = $form->getGameOfficials();
        foreach ($gameOfficials as $index => $gameOfficial) {
            $gameOfficialOriginal = $gameOfficialsOriginal[$gameOfficial->slot];

            // Process some commands
            $assignState = $gameOfficial->assignState;
            switch ($assignState) {
                case 'RemoveByAssignor':
                case 'Rejected':
                case 'TurnBackApproved':
                    $gameOfficial->assignState = 'Open';
                    $gameOfficial->regPersonId = null;
                    break;
            }
            if(!empty($conflicts[$index])){
                $gameOfficial->assignState = 'Open';
                $gameOfficial->regPersonId = null;
            };

            $this->gameOfficialUpdater->updateGameOfficial($gameOfficial, $gameOfficialOriginal);
        }
        if ($form->isValid()) {
            return $redirect;
        }

        $request->attributes->set('game', $game);

        return null;
    }
}
