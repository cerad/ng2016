<?php

namespace AppBundle\Action;

use PHPExcel;
use PHPExcel_IOFactory;

//    $excel = new AbstractImporter('path/to/file.xls');
//
//    var_dump($excel->toArray());
//
//    // Sample array of data returned
//    $arrayData = array(
//        array(NULL,   2010, 2011, 2012),   //heading labels; NULL for row labels
//        array('Q1',   12,   15,   21),
//        array('Q2',   56,   73,   86),
//        array('Q3',   52,   61,   69),
//        array('Q4',   30,   32,    0),
//    );

class AbstractImporter
{
    private $objPHPExcel;

    protected function import($file)
    {
        $wb = null;
        
        //accept file objects or filenames
        if($file instanceof \SplFileInfo){
            $fileName = $file->getRealPath();
        }else{
            $fileName = $file;
        }
        
        //construct the importer object
        //load the file
        $this->objPHPExcel = PHPExcel_IOFactory::load($fileName);
        $xl = $this->objPHPExcel;

        //load the workbook into array
        $wbArray = [];

        $wsIterator = $xl->getWorksheetIterator();
        
        foreach ($wsIterator as $ws) {
            $index = $wsIterator->key();
            
            $xl->setActiveSheetIndex($index);
            
            $wbArray[] = $this->importActiveSheet();
        }
        
        foreach (array_values($wbArray) as $index=>$division) {
            foreach($division as $div => $teams) {
                $wb[$div] = $teams;
            }
        }

        return $wb;
    }
    /*
     * Create array from worksheet
     *
     * @param mixed $nullValue Value returned in the array entry if a cell doesn't exist
     * @param boolean $calculateFormulas Should formulas be calculated?
     * @param boolean $formatData  Should formatting be applied to cell values?
     * @param boolean $returnCellRef False - Return a simple array of rows and columns indexed by number counting from zero
     *                               True - Return rows and columns indexed by their actual row and column IDs
     * @return array
     */
    protected function importActiveSheet($nullValue = null, $calculateFormulas = true, $formatData = false)
	{
		$data = [];
		
        $ws = $this->objPHPExcel->getActiveSheet();
        $wsName = $ws->getTitle();

    	$rows = $ws->toArray($nullValue,$calculateFormulas,$formatData,false);
		$headers = array_shift($rows);
		
		array_walk($rows, function(&$values) use($headers){
			$values = array_combine($headers, $values);
		});
        
        foreach($rows as $key=>$row) {
            if ($key == 0) {
                $data[] = array_keys($row);
            }
            $data[] = array_values($row);

        }
        $result[$wsName] = $data;
        
        return $result;
    }
    
}
