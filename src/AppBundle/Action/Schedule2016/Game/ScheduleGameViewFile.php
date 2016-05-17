<?php

namespace AppBundle\Action\Schedule2016\Game;

use AppBundle\Action\AbstractView;
use AppBundle\Action\AbstractExporter;

use AppBundle\Action\Schedule2016\ScheduleGame;
use AppBundle\Action\Schedule2016\ScheduleTemplate;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ScheduleGameViewFile extends AbstractView
{
    private $outFileName;
    private $scheduleRepository;
    private $exporter;
    
    public function __construct(AbstractExporter $exporter)
    {
        $this->outFileName =  'GameSchedule2016.' . date('Ymd_His') . '.' . $exporter->fileExtension;
     
        $this->exporter = $exporter;
    }
    public function __invoke(Request $request)
    {
        $projectGames = $request->attributes->get('games');

        $content = $this->generateResponse($projectGames);
        $exporter = $this->exporter;

        $response = new Response();

        $response->setContent($exporter->export($content));

        $response->headers->set('Content-Type', $exporter->contentType);

        $response->headers->set('Content-Disposition', 'attachment; filename='. $this->outFileName);

        return $response;
    }
    protected function generateResponse($projectGames)
    {
        //set the header labels
        $data =   array(
            array ('Game','Day','Time','Field','Group','Home Slot','Home Team','Away Slot','Away Team')
        );
        
        //set the data : game in each row
        foreach ( $projectGames as $projectGame ) {
//var_dump($projectGame); die();            
            $projectGameTeamHome = $projectGame->homeTeam;
            $projectGameTeamAway = $projectGame->awayTeam;

            $data[] = array(
                $projectGame->gameNumber,
                $projectGame->dow,
                $projectGame->time,
                $projectGame->fieldName,
                $projectGame->poolView,
                $projectGameTeamHome->poolTeamSlotView,
                $projectGameTeamHome->regTeamName,
                $projectGameTeamAway->poolTeamSlotView,
                $projectGameTeamAway->regTeamName
            );
        }

        $workbook['GameSchedule']['data'] = $data;

        return $workbook;

    }
}
