<?php

namespace AppBundle\Action\Schedule\Official;

use AppBundle\Action\AbstractView2;
use AppBundle\Action\AbstractExporter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ScheduleOfficialViewFile extends AbstractView2
{
    private $outFileName;
    private $scheduleRepository;
    private $exporter;

    public function __construct(AbstractExporter $exporter)
    {
        $this->outFileName =  'Officials_Schedule.' . date('Ymd_His') . '.' . $exporter->fileExtension;

        $this->exporter = $exporter;
    }
    public function __invoke(Request $request)
    {
        $exporter = $this->exporter;

        $games = $request->attributes->get('games');

        $response = new Response();
        // generate the response
        $content = $this->generateResponse($games);

        $response->setContent($exporter->export($content));

        $response->headers->set('Content-Type', $exporter->contentType);

        $response->headers->set('Content-Disposition', 'attachment; filename='. $this->outFileName);

        return $response;
    }
    protected function generateResponse($games)
    {
        //set the header labels
        $data =   array(
            array ('Game','Day','Time','Field','Division','Home Team Pool','Home Team','Away Team','Away Team Pool','Referee','AR1','AR2')
        );

        //set the data : game in each row
        foreach ( $games as $game ) {
            $teamHome = $game->homeTeam;
            $teamAway = $game->awayTeam;
            $division = $teamHome->division;

            $data[] = array(
                $game->gameNumber,
                $game->dow,
                $game->time,
                $game->fieldName,
                $division,
                $teamHome->poolTeamView,
                $teamHome->regTeamName,
                $teamAway->regTeamName,
                $teamAway->poolTeamView,
                $game->referee->regPersonName,
                $game->ar1->regPersonName,
                $game->ar2->regPersonName,
            );

        }

        $response['GameSchedule']['data'] = $data;
        $response['GameSchedule']['options']['freezePane'] = 'A2';
        $response['GameSchedule']['options']['horizontalAlignment'] = 'left';

        return $response;
    }
}
