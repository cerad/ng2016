<?php

namespace AppBundle\Action\Schedule\Game;

use AppBundle\Action\AbstractView;
use AppBundle\Action\AbstractExporter;

use AppBundle\Action\Schedule\ScheduleRepository;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpFoundation\Request;

class ScheduleGameViewExtra extends AbstractView
{
    private $outFileName;
    private $scheduleRepository;
    private $exporter;
    
    public function __construct(ScheduleRepository $scheduleRepository, AbstractExporter $exporter)
    {
        $this->scheduleRepository = $scheduleRepository;
        
        $this->outFileName =  'GameScheduleExtra' . '_' . date('Ymd_His') . '.' . $exporter->fileExtension;
     
        $this->exporter = $exporter;
    }
    public function __invoke(Request $request)
    {
        $search = array(
            'projects'  => array($this->project['key']),
            'programs'  => array('Extra'),
        );

        $projectGames = $this->scheduleRepository->findProjectGames($search);

        // generate the response
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
            
            $projectGameTeamHome = $projectGame['teams'][1];
            $projectGameTeamAway = $projectGame['teams'][2];

            $data[] = array(
                $projectGame['number'],
                $projectGame['dow'],
                $projectGame['time'],
                $projectGame['field_name'],
                $projectGame['group_name'],
                $projectGameTeamHome['group_slot'],
                $projectGameTeamHome['name'],
                $projectGameTeamAway['group_slot'],
                $projectGameTeamAway['name']
            );
        }

        return $data;

    }

}
    