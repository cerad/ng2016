<?php

namespace AppBundle\Action\Schedule\Game;

use AppBundle\Action\Schedule\AbstractExport;

use AppBundle\Action\Schedule\ScheduleRepository;

use Symfony\Component\HttpFoundation\Request;

use PHPExcel;
use PHPExcel_IOFactory;

class ScheduleGameViewExtra extends AbstractExport 
{
    private $outFilename;
    
    private $xlsWriterType = 'Excel2007';
    private $xlsExt = '.xlsx';
    private $xlsContentType = "application/vnd.ms-excel"; 
    
    public function __construct(ScheduleRepository $scheduleRepository)
    {
        parent::__construct($scheduleRepository);
        
        $this->outFilename =  $this->outFileNameSchedule . 'GameExtra' . $this->xlsExt;
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
            $this->xlsWriterType,
            $this->xlsContentType,
            $this->outFilename
        );

        return $response;
    
    }
    

}
    