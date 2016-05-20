<?php
namespace AppBundle\Action\GameOfficial\AssignByAssignee;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Game\GameFinder;
use AppBundle\Action\GameReport2016\GameReport;
use AppBundle\Action\GameReport2016\GameReportRepository;
use AppBundle\Action\GameReport2016\GameReportPointsCalculator;

use Symfony\Component\HttpFoundation\Request;

class AssigneeController extends AbstractController2
{
    /** @var  AssigneeForm */
    private $form;

    /** @var GameFinder  */
    private $gameFinder;
    
    public function __construct(
        AssigneeForm $form,
        GameFinder   $gameFinder
    ) {
        $this->form       = $form;
        $this->gameFinder = $gameFinder;
    }
    public function __invoke(Request $request, $projectId, $gameNumber, $slot)
    {
        // Stash the back link in the session
        $session = $request->getSession();
        $sessionKey = 'game_report_update_back';
        if ($request->query->has('back')) {
            $session->set($sessionKey,$request->query->get('back'));
            return $this->redirectToRoute(
                $this->getCurrentRouteName(),
                ['projectId' => $projectId,'gameNumber' => $gameNumber, 'slot' => $slot]
            );
        }
        $backRouteName = 'schedule_official_2016'; // Inject or results link
        if ($session->has($sessionKey)) {
            $backRouteName = $session->get($sessionKey);
        }
        $game = $this->gameFinder->findGame($projectId,$gameNumber);
        if (!$game) {
            return $this->redirectToRoute('app_home');
        }
        $gameOfficial = $game->getOfficial($slot);
        
        $form = $this->form;
        $form->setGame($game);
        $form->setGameOfficial($gameOfficial);
        $form->setBackRouteName($backRouteName);
        $form->handleRequest($request);

        if ($form->isValid()) {

            /** @var GameReport $gameReport */
            $gameReport = $form->getGameReport();

             if ($gameReport->reportState === 'Clear') {
                 $gameReport->clearReport();
                 $this->gameReportRepository->updateGameReport($gameReport);
                 return $this->redirectToRoute(
                     $this->getCurrentRouteName(),
                     ['projectId' => $projectId,'gameNumber' => $gameNumber]
                 );
            }
            // Kind of a strange case so just drop through
            if (!$gameReport->hasScores()) {
                $request->attributes->set('gameReport', $gameReport);
                return null;
            }
            // Points
            $pointsCalculator = $this->pointsCalculator;
            $gameReport = $pointsCalculator($gameReport);

            // Status state stuff, TODO deal with forfeits etc
            switch($gameReport->status) {
                case 'Normal':
                case 'InProgress':
                    $gameReport->status = 'Played';
                    break;
            }
            switch($gameReport->reportState) {
                case 'Initial':
                case 'Pending':
                    $gameReport->reportState = 'Submitted';
                    break;
            }
            // Save
            $this->gameReportRepository->updateGameReport($gameReport);

            // Deal with next
            if ($request->request->has('next')) {
                $gameNumber = $request->request->get('nextGameNumber');
            }
            return $this->redirectToRoute(
                $this->getCurrentRouteName(),
                ['projectId' => $projectId,'gameNumber' => $gameNumber]
            );
        }
        $request->attributes->set('game',$game);
        return null;
    }
}
