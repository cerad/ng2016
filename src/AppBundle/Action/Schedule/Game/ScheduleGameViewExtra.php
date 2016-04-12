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
    
    public function __construct(ScheduleRepository $scheduleRepository)
    {
        $this->scheduleRepository = $scheduleRepository;
        
        $this->outFileName =  date('Ymd_His') . '_' . 'GameScheduleExtra.xlsx';
     
        $this->exporter = new AbstractExporter();
    }
    public function __invoke(Request $request)
    {
        $this->search = array(
            'dates'     => array_keys($this->project['dates']),
            'ages'      => array_keys($this->project['ages']),
            'projects'  => array($this->project['key']),
            'programs'  => array('Extra'),
            'genders'   => array_keys($this->project['genders']),
        );

        // generate the response
        $response = $this->generateResponse(
            $this->scheduleRepository->findProjectGames($this->search),
            $this->outFileName
        );

        return $response;
    
    }
    
    protected function generateResponse($projectGames, $outFilename)
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
        $xlsx = $this->exporter->exportXLSX($data);

        //generate the response with this content
        $response = new Response();
        
        $response->setContent($xlsx);
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', "attachment; filename=".$outFilename);

        return $response;

    }

}
    