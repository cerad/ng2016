<?php

namespace AppBundle\Action\Schedule\Game;

use AppBundle\Action\Schedule\AbstractExport;

use AppBundle\Action\Schedule\ScheduleRepository;

use Symfony\Component\HttpFoundation\Request;

use PHPExcel;
use PHPExcel_IOFactory;

class ScheduleGameViewCsv extends AbstractExport 
{
    private $outFilename;

    private $csvWriterType = 'CSV';
    private $csvExt = '.csv';
    private $csvContentType = "text/csv"; 
        
    public function __construct(ScheduleRepository $scheduleRepository)
    {
        parent::__construct($scheduleRepository);
        
        $this->outFilename =  $this->outFileNameSchedule . 'Game' . $this->csvExt;
    }
    public function __invoke(Request $request)
    {
        $this->search = $request->attributes->get('schedule_game_search');

        // generate the response
        $response = $this->generateResponse(
            $this->scheduleRepository->findProjectGames($this->search),
            $this->csvWriterType,
            $this->csvContentType,
            $this->outFilename
        );
            
        return $response;
    
    }
    

}
    