<?php

namespace AppBundle\Action\Schedule\My;

use AppBundle\Action\AbstractView2;
use AppBundle\Action\AbstractExporter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use PhpOffice\PhpSpreadsheet;

class ScheduleMyViewFile extends AbstractView2
{
    private $outFileName;
    private $exporter;

    /**
     * ScheduleMyViewFile constructor.
     * @param AbstractExporter $exporter
     */
    public function __construct(AbstractExporter $exporter)
    {
        $this->outFileName =  'MySchedule.' . date('Ymd_His') . '.' . $exporter->fileExtension;

        $this->exporter = $exporter;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws PhpSpreadsheet\Exception
     * @throws PhpSpreadsheet\Writer\Exception
     */
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

    /**
     * @param $games
     * @return mixed
     */
    protected function generateResponse($games)
    {
        //set the header labels
        $data =   array(
            array ('Game','Day','Time','Field','Group','Home Team Pool','Home Team','Away Team','Away Team Pool','Referee', 'Asst Ref 1', 'Asst Ref 2')
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
                $teamAway->poolTeamKey,
                ucwords(strtolower($game->referee->regPersonName)),
                ucwords(strtolower($game->ar1->regPersonName)),
                ucwords(strtolower($game->ar2->regPersonName))
            );

        }

        $response['MySchedule']['data'] = $data;
        $response['MySchedule']['options']['freezePane'] = 'A2';

        return $response;
    }
}
