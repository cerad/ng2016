<?php
namespace AppBundle\Action\GameReport\Update;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\GameReport\GameReport;
use AppBundle\Action\GameReport\GameReportRepository;
use AppBundle\Action\GameReport\GameReportPointsCalculator;

use Symfony\Component\HttpFoundation\Request;

class GameReportUpdateController extends AbstractController2
{
    /** @var  GameReportUpdateForm */
    private $form;

    /** @var GameReportRepository  */
    private $gameReportRepository;

    /** @var  GameReportPointsCalculator */
    private $pointsCalculator;

    public function __construct(
        GameReportUpdateForm       $form,
        GameReportRepository       $gameReportRepository,
        GameReportPointsCalculator $pointsCalculator
    )
    {
        $this->form                 = $form;
        $this->pointsCalculator     = $pointsCalculator;
        $this->gameReportRepository = $gameReportRepository;
    }
    public function __invoke(Request $request, $projectId, $gameNumber)
    {
        // Stash the back link in the session
        $session = $request->getSession();
        $sessionKey = 'game_report_update_back';
        if ($request->query->has('back')) {
            $session->set($sessionKey,$request->query->get('back'));
            return $this->redirectToRoute(
                $this->getCurrentRouteName(),
                ['projectId' => $projectId,'gameNumber' => $gameNumber]
            );
        }
        $backRouteName = 'schedule_team_2019'; // Inject or results link
        if ($session->has($sessionKey)) {
            $backRouteName = $session->get($sessionKey);
        }
        // Get the report
        $gameReport = $this->gameReportRepository->findGameReport($projectId,$gameNumber);
        if (!$gameReport) {
            return $this->redirectToRoute('app_home');
        }
        $form = $this->form;
        $form->setGameReport($gameReport);
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
        $request->attributes->set('gameReport',$gameReport);
        return null;
    }
}
