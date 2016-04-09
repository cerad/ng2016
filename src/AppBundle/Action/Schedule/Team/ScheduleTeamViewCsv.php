<?php

namespace AppBundle\Action\Schedule\Team;

use AppBundle\Action\Schedule\AbstractExport;

use AppBundle\Action\Schedule\ScheduleRepository;

use Symfony\Component\HttpFoundation\Request;

use PHPExcel;
use PHPExcel_IOFactory;

class ScheduleTeamViewCsv extends AbstractExport 
{
    private $outFilename;

    private $csvWriterType = 'CSV';
    private $csvExt = '.csv';
    private $csvContentType = "text/csv"; 
        
    public function __construct(ScheduleRepository $scheduleRepository)
    {
        parent::__construct($scheduleRepository);
        
        $this->outFilename =  $this->outFileNameSchedule . 'Team' . $this->csvExt;
    }
    public function __invoke(Request $request)
    {
        $projectTeamKeys = $request->attributes->get('projectTeamKeys');

        // generate the response
        $response = $this->generateResponse(
            $this->scheduleRepository->findProjectGamesForProjectTeamKeys($projectTeamKeys),
            $this->csvWriterType,
            $this->csvContentType,
            $this->outFilename
        );
            
        return $response;
    
    }
    

}
    