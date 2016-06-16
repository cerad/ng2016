<?php

namespace AppBundle\Action;

use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Style_Protection;
use PHPExcel_Style_Alignment;

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
            case 'txt':
                $this->fileExtension = "txt";
                $this->contentType = "text/plain";
        }
    }
    public function export($content)
    {
        switch ($this->format) {
            case 'csv': return $this->exportCSV ($content);
            case 'xls': return $this->exportXLSX($content);
            case 'txt': return $this->exportTxt($content);
        }
    }
    public function exportTxt($content, $padlen = 18)
    {
        $tmpName = tempnam('','exporttxt.txt');

        $file = fopen($tmpName, 'w');

        foreach ($content['GameSchedule']['data'] as $row) {
            $line = '';
            foreach($row as $item){
                $line .= str_pad($item,$padlen,' ',STR_PAD_LEFT);
            }
            fwrite($file, $line."\n");
        }
        fclose($file);

        $contents = file_get_contents($tmpName);

        unlink($tmpName);

        return $contents;

    }
    public function exportCSV($content) {

        //for csv type, only export first sheet
        $content = array_values($content);

        $this->writeWorksheet($content[0]);

        $objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'CSV');

        ob_start();
        $objWriter->save('php://output'); // Instead of file name

        return ob_get_clean();

    }
    public function is_asso($a)
    {
        foreach(array_keys($a) as $key)
            if (!is_int($key)) return true;

        return false;
    }
    public function exportXLSX($content, $sheetName = 'Sheet')
    {
        $xl = $this->objPHPExcel;

        //check for sheet names as keys
        $isAssoc = $this->is_asso($content);

        // ensure unique sheetname
        foreach ($content as $shName=>$data) {
            if ($isAssoc) {
                $sheetName = $shName;
            }

            $xl->createSheet();
            $xl->setActiveSheetIndex($xl->getSheetCount()-1);

            $this->writeWorksheet($data, $sheetName);
        }

        //remove first sheet -- is blank
        $ws = $xl->removeSheetByIndex(0);

        //write to application output buffer
        $objWriter = PHPExcel_IOFactory::createWriter($xl, 'Excel2007');

        ob_start();
        $objWriter->save('php://output'); // Instead of file name

        return ob_get_clean();

    }
    public function writeWorksheet($content, $shName="Sheet")
    {
        //check for data
        if (!isset($content['data'])) return null;

        //get data
        $data = $content['data'];
        //get options (if any)
        $options = isset($content['options']) ? $content['options'] : null;

        //select active sheet
        $ws = $this->objPHPExcel->getActiveSheet();

        //load data into sheet
        $ws->fromArray($data, NULL, 'A1');

        //auto-size columns
        foreach(range('A',$ws->getHighestDataColumn()) as $col) {
            $ws->getColumnDimension($col)->setAutoSize(true);
        }

        //apply options
        if (isset($options['hideCols'])){
            // Hide sheet columns.
            $cols = $options['hideCols'];
            foreach ($cols as $col) {
                $ws->getColumnDimension($col)->setVisible(FALSE);
            }
        }

        //freeze pane
        //$options['freezePane'] = 'A2';
        if (isset($options['freezePane'])){
            $ws->freezePane($options['freezePane']);
        }
        
        //horizontal alignment
        //$options['horizontalAlignment'] = 'left';
        if (isset($options['horizontalAlignment'])){
            switch ($options['horizontalAlignment']) {
                case 'center':
                    $ws->getStyle( $ws->calculateWorksheetDimension() )->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  
                    break;
                case 'general':
                    $ws->getStyle( $ws->calculateWorksheetDimension() )->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_GENERAL);                      
                    break;
                case 'justify':
                    $ws->getStyle( $ws->calculateWorksheetDimension() )->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_JUSTIFY);                                          
                    break;
                case 'left':
                    $ws->getStyle( $ws->calculateWorksheetDimension() )->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);                                          
                    break;
                case 'right':
                    $ws->getStyle( $ws->calculateWorksheetDimension() )->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);                                          
                    break;
            }
        }


        //protect cells
        //$options['protect'] = array('pw' => '2016NG', 'range' => array('A:D'));
        //reference: http://stackoverflow.com/questions/20543937/disable-few-cells-in-phpexcel

        if (isset($options['protection']['pw']) and isset($options['protection']['unlocked'])) {
            $pw = $options['protection']['pw'];
            $range = $options['protection']['unlocked'];

            //turn protection on
            $ws->getProtection()->setSheet(true);
            
            //now unprotect requested range
            foreach ($range as $cells) {
                $ws->getStyle($cells)->getProtection()->setLocked(PHPExcel_Style_Protection::PROTECTION_UNPROTECTED);
            }
        }

        //ensure sheet name is unique
        $inc = 1;
        $name = $shName;
        while (!is_null($this->objPHPExcel->getSheetByName($name) ) ){
            $name = $shName . $inc;
            $inc += 1;
        }

        //$shName = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $name);

        //Excel limit sheet names to 31 characters
        if (strlen($shName) > 31) {
            $shName = substr($name, -31);
        }

        //name the sheet
        $ws->setTitle($shName);

        return;

    }
}

