<?php

namespace AppBundle\Action\Schedule;

use AppBundle\Action\AbstractView;

use AppBundle\Action\Schedule\ScheduleRepository;

use Symfony\Component\HttpFoundation\Response;

use PHPExcel;
use PHPExcel_IOFactory;

class AbstractExport extends AbstractView
{
    /** @var  ScheduleRepository */
    protected $scheduleRepository;
    
    protected $outFileNameTeamSchedule = 'TeamSchedule';
    protected $extension;
    
    public function __construct(ScheduleRepository $scheduleRepository)
    {
        $this->scheduleRepository = $scheduleRepository;
        
        $this->outFileNameTeamSchedule =  date('Ymd_His') . '_' . $this->outFileNameTeamSchedule;
    }

    protected function generateBuffer($projectGames, $extension) {
        
        $objPHPExcel = $this->generateExcelObject($projectGames);
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, $extension);
        
        ob_start();
        $objWriter->save('php://output'); // Instead of file name
        return ob_get_clean();
        
    }
    
    protected function generateResponse($projectGames, $writerType, $contentType, $outFilename) {
        
        $response = new Response();
        
        $response->setContent($this->generateBuffer($projectGames, $writerType) );
        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('Content-Disposition', "attachment; filename=".$outFilename);
        
        return $response;

    }
    
    protected function generateExcelObject($projectGames){

        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();
        
        $row = 1;
        $objPHPExcel->getActiveSheet()->setCellValue('A'.$row, 'Game')
                                      ->setCellValue('B'.$row, 'Day')
                                      ->setCellValue('C'.$row, 'Time')
                                      ->setCellValue('D'.$row, 'Field')
                                      ->setCellValue('E'.$row, 'Group')
                                      ->setCellValue('F'.$row, 'Home Slot')
                                      ->setCellValue('G'.$row, 'Home Team')
                                      ->setCellValue('H'.$row, 'Away Slot')
                                      ->setCellValue('I'.$row, 'Away Team');
        $row++;
        
        foreach ( $projectGames as $projectGame ) {
            
            $projectGameTeamHome = $projectGame['teams'][1];
            $projectGameTeamAway = $projectGame['teams'][2];

            $objPHPExcel->getActiveSheet()->setCellValue('A'.$row, $projectGame['number'])
                                          ->setCellValue('B'.$row, $projectGame['dow'])
                                          ->setCellValue('C'.$row, $projectGame['time'])
                                          ->setCellValue('D'.$row, $projectGame['field_name'])
                                          ->setCellValue('E'.$row, $projectGame['group_name'])
                                          ->setCellValue('F'.$row, $projectGameTeamHome['group_slot'])
                                          ->setCellValue('H'.$row, $projectGameTeamHome['name'])
                                          ->setCellValue('G'.$row, $projectGameTeamAway['group_slot'])
                                          ->setCellValue('I'.$row, $projectGameTeamAway['name']);
            $row++;
        }
        
        return $objPHPExcel;

    }
}
    