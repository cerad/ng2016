<?php

namespace AppBundle\Action\GameReport2016\Update;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\GameReport2016\GameReportRepository;
use AppBundle\Action\GameReport2016\GameReportPointsCalculator;

use Symfony\Component\HttpFoundation\Request;

class GameReportUpdateController extends AbstractController2
{
    /** @var GameReportRepository  */
    private $gameReportRepository;

    /** @var  GameReportPointsCalculator */
    private $pointsCalculator;
    
    private $project;

    public function __construct(
        GameReportRepository $gameReportRepository,
        GameReportPointsCalculator     $pointsCalculator
    )
    {
        $this->pointsCalculator     = $pointsCalculator;
        $this->gameReportRepository = $gameReportRepository;
    }
    public function __invoke(Request $request, $projectId, $gameNumber)
    {
        // Get the report
        $gameReport = $this->gameReportRepository->findGameReport($projectId,$gameNumber);
        if (!$gameReport) {
            return $this->redirectToRoute('app_home');
        }
        // Should probably verify permissions based on project
        
        if ($request->isMethod('POST')) {
            
            // Posted data
            $gameReportPosted = $request->request->get('gameReport');

            $this->processGameReport($gameReport,$gameReportPosted);

            if ($request->request->has('next')) {
                $gameNumber = $request->request->get('nextGameNumber');
            }
            return $this->redirectToRoute('game_report_update',['gameNumber' => $gameNumber]);
        }
        $request->attributes->set('gameReport',$gameReport);

        return null;
    }
    private function processGameReport($gameReportOriginal,$gameReportPosted)
    {
        // Can I just use array_merge_recursive on the entire game report?
        // Nope, team keys get duplicated, maybe if role was used
        //$gameReportPosted = array_merge_recursive($gameReportOriginal,$gameReportPosted);

        // Merge here because not every form has inputs for all possible items
        $gameReportPosted['teamReports'][1] = array_merge($gameReportOriginal['teamReports'][1],$gameReportPosted['teamReports'][1]);
        $gameReportPosted['teamReports'][2] = array_merge($gameReportOriginal['teamReports'][2],$gameReportPosted['teamReports'][2]);

        // Update points earned
        $gameReportPosted = $this->pointsCalculator->calcPointsForGameReport($gameReportPosted);
        
        // Clean up notes
        $gameReportPosted['notes'] = strip_tags($gameReportPosted['notes']);
        
        // Fool with status

        // And update
        $this->gameReportRepository->update($gameReportOriginal,$gameReportPosted);
        
        //dump($gameReportPosted);
    }
}
