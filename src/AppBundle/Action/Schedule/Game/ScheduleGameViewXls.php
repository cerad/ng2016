<?php

namespace AppBundle\Action\Schedule\Game;

use AppBundle\Action\Schedule\AbstractExport;

use AppBundle\Action\Schedule\ScheduleRepository;

use Symfony\Component\HttpFoundation\Request;

use PHPExcel;
use PHPExcel_IOFactory;

class ScheduleGameViewXls extends AbstractExport 
{
    private $outFilename;
    
    private $xlsWriterType = 'Excel2007';
    private $xlsExt = '.xlsx';
    private $xlsContentType = "application/vnd.ms-excel"; 
    
    public function __construct(ScheduleRepository $scheduleRepository)
    {
        parent::__construct($scheduleRepository);
        
        $this->outFilename =  $this->outFileNameSchedule . 'Game' . $this->xlsExt;
    }
    public function __invoke(Request $request)
    {
        $this->search = $request->attributes->get('schedule_game_search');

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
    