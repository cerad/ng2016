<?php
namespace AppBundle\Common;

use \PHPExcel as WorkBook;

use \PHPExcel_Worksheet as WorkSheet;

trait ExcelWriterTrait
{
    /** @var WorkBook */
    private $wb;

    private function setCellValue(Worksheet $ws,$col,$row,$value)
    {
        $ws->getCell($col . $row)->setValue($value);
    }
    private function setCellValueNumeric(\PHPExcel_Worksheet $ws,$col,$row,$value)
    {
        $ws->getCell($col . $row)->setValueExplicit($value,\PHPExcel_Cell_DataType::TYPE_NUMERIC);
    }
    private function setCellValueString(\PHPExcel_Worksheet $ws, $col, $row, $value)
    {
        $ws->getCell($col . $row)->setValueExplicit($value,\PHPExcel_Cell_DataType::TYPE_STRING);
    }
    private function setCellFormat(\PHPExcel_Worksheet $ws,$col,$row,$format)
    {
        $ws->getStyle($col . $row)->getNumberFormat()->setFormatCode($format);
    }
    private function setCellFillColor(\PHPExcel_Worksheet $ws,$col,$row,$color)
    {
        $ws->getStyle($col . $row)->applyFromArray([
            'fill' => [
                'type'  => \PHPExcel_Style_Fill::FILL_SOLID,
                'color' => ['rgb' => $color]
            ] 
        ]);
    }
    private function setCellValueDate(\PHPExcel_Worksheet $ws,$col,$row,$dt,$format = 'ddd')
    {
        if (!$dt) return;
        
        $date = substr($dt, 0, 10);
        $dateValue = \PHPExcel_Shared_Date::stringToExcel($date);
        
        $this->setCellFormat      ($ws,$col,$row,$format);
        $this->setCellValueNumeric($ws,$col,$row,$dateValue);
    }
    private function setCellValueTime(\PHPExcel_Worksheet $ws,$col,$row,$dt,$format = '[$-409]h:mm AM/PM;@')
    {
        if (!$dt) return;
        
        $time = substr($dt,10);
        
        list($h, $m, $s) = explode(':', $time);
        $timeValue = $h / 24 + $m / 1440 + $s / 86400;

        $this->setCellFormat      ($ws,$col,$row,$format);
        $this->setCellValueNumeric($ws,$col,$row,$timeValue);
    }
    private function setColWidth(\PHPExcel_Worksheet $ws,$col,$width)
    {
        $ws->getColumnDimension($col )->setWidth($width);
    }
    private function setColAlignCenter(\PHPExcel_Worksheet $ws,$col)
    {
        $ws->getStyle($col)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    }
    private function createWorkBook()
    {
        // Not sure this is needed
        \PHPExcel_Cell::setValueBinder(new \PHPExcel_Cell_AdvancedValueBinder());

        $this->wb = $wb = new WorkBook();
        
        return $wb;
    }
    private function getContents()
    {
        $writer = \PHPExcel_IOFactory::createWriter($this->wb, "Excel2007");
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }
    public function getFileExtension()
    {
        return 'xlsx';
    }
    public function getContentType()
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }
    
}