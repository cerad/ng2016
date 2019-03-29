<?php

namespace AppBundle\Action\Schedule2019\Game;

use AppBundle\Action\AbstractView2;
use AppBundle\Action\AbstractExporter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ScheduleGameViewFile extends AbstractView2
{
    private $outFileName;
    private $scheduleRepository;
    private $exporter;

    public function __construct(AbstractExporter $exporter)
    {
        $this->outFileName =  'GameSchedule2019.' . date('Ymd_His') . '.' . $exporter->fileExtension;

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
            array ('Game','Day','Time','Field','Group','Home Team Pool','Home Team','Away Team','Away Team Pool')
        );

        //set the data : game in each row
        foreach ( $games as $game ) {
            $teamHome = $game->homeTeam;
            $teamAway = $game->awayTeam;
            
            $poolView = $game->poolView;
            $sep = '<hr class="separator">';
            if (strpos($poolView, $sep) !== false) {
                $poolView = str_replace($sep, ' / ', $poolView);
            }

            $data[] = array(
                $game->gameNumber,
                $game->dow,
                $game->time,
                $game->fieldName,
                $poolView,
                $teamHome->poolTeamKey,
                $teamHome->regTeamName,
                $teamAway->regTeamName,
                $teamAway->poolTeamKey
            );

        }

        $response['GameSchedule']['data'] = $data;
        $response['GameSchedule']['options']['freezePane'] = 'A2';

        return $response;
    }
}
