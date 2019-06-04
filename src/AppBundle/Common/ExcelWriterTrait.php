<?php
namespace AppBundle\Common;

use PhpOffice\PhpSpreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

trait ExcelWriterTrait
{
    /** @var PhpSpreadsheet\Spreadsheet */
    private $wb;

    private function setCellValue(Worksheet $ws,$col,$row,$value)
    {
        $ws->getCell($col . $row)->setValue($value);
    }
    private function setCellValueNumeric(Worksheet $ws,$col,$row,$value)
    {
        $ws->getCell($col . $row)->setValueExplicit($value,PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
    }
    private function setCellValueString(Worksheet $ws, $col, $row, $value)
    {
        $ws->getCell($col . $row)->setValueExplicit($value,PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    }
    private function setCellFormat(Worksheet $ws,$col,$row,$format)
    {
        $ws->getStyle($col . $row)->getNumberFormat()->setFormatCode($format);
    }
    private function setCellFillColor(Worksheet $ws,$col,$row,$color)
    {
        $ws->getStyle($col . $row)->applyFromArray([
            'fill' => [
                'type'  => PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => $color]
            ] 
        ]);
    }
    private function setCellValueDate(Worksheet $ws,$col,$row,$dt,$format = 'ddd')
    {
        if (!$dt) return;
        
        $date = substr($dt, 0, 10);
        $dateValue = PhpSpreadsheet\Shared\Date::stringToExcel($date);
        
        $this->setCellFormat      ($ws,$col,$row,$format);
        $this->setCellValueNumeric($ws,$col,$row,$dateValue);
    }
    private function setCellValueTime(Worksheet $ws,$col,$row,$dt,$format = '[$-409]h:mm AM/PM;@')
    {
        if (!$dt) return;
        
        $time = substr($dt,10);
        
        list($h, $m, $s) = explode(':', $time);
        $timeValue = $h / 24 + $m / 1440 + $s / 86400;

        $this->setCellFormat      ($ws,$col,$row,$format);
        $this->setCellValueNumeric($ws,$col,$row,$timeValue);
    }
    private function setColWidth(Worksheet $ws,$col,$width)
    {
        $ws->getColumnDimension($col )->setWidth($width);
    }
    private function setColAlignCenter(Worksheet $ws,$col)
    {
        $ws->getStyle($col)->getAlignment()->setHorizontal(PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    }
    private function createWorkBook()
    {
        // Not sure this is needed
        PhpSpreadsheet\Cell\Cell::setValueBinder(new PhpSpreadsheet\Cell\AdvancedValueBinder());

        $this->wb = $wb = new PhpSpreadsheet\Spreadsheet();
        
        return $wb;
    }
    private function getContents()
    {
        $writer = PhpSpreadsheet\IOFactory::createWriter($this->wb, "Xlsx");
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