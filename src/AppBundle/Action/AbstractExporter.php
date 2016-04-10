<?php

namespace AppBundle\Action;

use PHPExcel;
use PHPExcel_IOFactory;

/*
    // Sample array of data to publish
    $arrayData = array(
        array(NULL, 2010, 2011, 2012),   //heading labels
        array('Q1',   12,   15,   21),
        array('Q2',   56,   73,   86),
        array('Q3',   52,   61,   69),
        array('Q4',   30,   32,    0),
    );
*/ 

class AbstractExporter
{
    private $objPHPExcel;
    
    public function __construct() {
        // Create new PHPExcel object
        $this->objPHPExcel = new PHPExcel();        
    }
    
    public function exportCSV($content) {
        
        $this->writeWorksheet($content);
        
        $objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'CSV');
        
        ob_start();
        $objWriter->save('php://output'); // Instead of file name
    
        return ob_get_clean();
        
    }
    
    public function exportXLSX($content, $sheetName = 'Sheet ')
    {
        // ensure unique sheetname    
        $inc = 1;
        $name = $sheetName;
        while (!is_null($this->objPHPExcel->getSheetByName($sheetName) ) ){
            $name = $sheetName . $inc;
            $inc += 1;
        }
        
        $this->writeWorksheet($content, $name);
        
        $objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel2007');
        
        ob_start();
        $objWriter->save('php://output'); // Instead of file name
    
        return ob_get_clean();
        
    }
    
    public function writeWorksheet($content)
    {
        $ws = $this->objPHPExcel->getActiveSheet();
        
        $ws->fromArray($content, NULL, 'A1');

        return;

    }
}
    