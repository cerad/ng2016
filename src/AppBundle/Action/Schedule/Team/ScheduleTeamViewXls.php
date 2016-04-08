<?php

namespace AppBundle\Action\Schedule\Team;

use AppBundle\Action\Schedule\AbstractExport;

use AppBundle\Action\Schedule\ScheduleRepository;

use Symfony\Component\HttpFoundation\Request;

use PHPExcel;
use PHPExcel_IOFactory;

class ScheduleTeamViewXls extends AbstractExport 
{
    private $outFilename;
    
    private $xlsWriterType = 'Excel2007';
    private $xlsExt = '.xlsx';
    private $xlsContentType = "application/vnd.ms-excel"; 
    
    public function __construct(ScheduleRepository $scheduleRepository)
    {
        parent::__construct($scheduleRepository);
        
        $this->outFilename =  $this->outFileNameTeamSchedule . $this->xlsExt;
    }
    public function __invoke(Request $request)
    {
        $projectTeamKeys = $request->attributes->get('projectTeamKeys');

        // generate the response
        $response = $this->generateResponse(
            $this->scheduleRepository->findProjectGamesForProjectTeamKeys($projectTeamKeys),
            $this->xlsWriterType,
            $this->xlsContentType,
            $this->outFilename
        );

        return $response;
    
    }
    

}
    