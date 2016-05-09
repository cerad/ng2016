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
    private $format;
    private $objPHPExcel;
    
    public $fileExtension;
    public $contentType;
    private $options;
    
    public function __construct($format) 
    {
        $this->format = $format;
        $this->objPHPExcel = new PHPExcel();  
        
        switch($format) {
            case 'csv':
                $this->fileExtension = 'csv';
                $this->contentType   = 'text/csv';
                break;
            case 'xls':
                $this->fileExtension = 'xlsx';
                $this->contentType   = 'application/vnd.ms-excel';
                break;
        }
    }
    public function export($content, $options = null)
    {
        $this->options = $options;
        
        switch ($this->format) {
            case 'csv': return $this->exportCSV ($content);
            case 'xls': return $this->exportXLSX($content);
        }
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
        
        foreach(range('A',$ws->getHighestDataColumn()) as $col) {
            $ws->getColumnDimension($col)->setAutoSize(true);
        }

        if (isset($this->options['hideCols'])){
            // Hide sheet columns.
            $cols = $this->options['hideCols'];
            foreach ($cols as $name) {
                $ws->getColumnDimension($name)->setVisible(FALSE);
            }
        }

        return;

    }
}
    