<?php

namespace AppBundle\Action\GameReport\Update;

use AppBundle\Action\AbstractController;
use AppBundle\Action\Project\ProjectFactory;
use AppBundle\Action\GameReport\GameReportRepository;
use AppBundle\Action\Results\PoolPlay\Calculator\PointsCalculator;

use Symfony\Component\HttpFoundation\Request;

class GameReportUpdateController extends AbstractController
{
    /** @var GameReportRepository  */
    private $gameReportRepository;

    /** @var  PointsCalculator */
    private $pointsCalculator;

    /** @var  ProjectFactory */
    private $projectFactory;

    private $project;

    public function __construct(
        GameReportRepository $gameReportRepository,
        PointsCalculator     $pointsCalculator,
        ProjectFactory       $projectFactory
    )
    {
        $this->projectFactory       = $projectFactory;
        $this->pointsCalculator     = $pointsCalculator;
        $this->gameReportRepository = $gameReportRepository;
    }
    public function __invoke(Request $request, $gameNumber)
    {
        $this->project = $project = $this->getCurrentProject()['info'];

        // Make sure signed in
        if (!$this->isGranted('ROLE_SCORE_ENTRY')) {
            //return $this->redirectToRoute('app_welcome');
        }
        // Get the report
        $gameReport = $this->gameReportRepository->find($project['key'],$gameNumber);
        if (!$gameReport) {
            return $this->redirectToRoute('app_home');
        }
        if ($request->isMethod('POST')) {
            // Check permissions
            
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

        //return new Response($this->pageTemplate->render($gameReport));
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
