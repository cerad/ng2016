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

        //freeze top row
        if (isset($options['freezePane'])){
            $ws->freezePane($options['freezePane']);
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

