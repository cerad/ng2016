<?php
namespace AppBundle\Common;

use PhpOffice\PhpSpreadsheet;
use PhpOffice\PhpSpreadsheet\Exception;
use \PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Trait ExcelWriterTrait
 * @package AppBundle\Common
 */
trait ExcelWriterTrait
{
    /** @var Spreadsheet */
    private $wb;

    /**
     * @param Worksheet $ws
     * @param $col
     * @param $row
     * @param $value
     * @throws Exception
     */
    private function setCellValue(Worksheet $ws,$col,$row,$value)
    {
        $ws->getCell($col . $row)->setValue($value);
    }

    /**
     * @param Worksheet $ws
     * @param $col
     * @param $row
     * @param $value
     * @throws Exception
     */
    private function setCellValueNumeric(Worksheet $ws,$col,$row,$value)
    {
        $ws->getCell($col . $row)->setValueExplicit($value,PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
    }

    /**
     * @param Worksheet $ws
     * @param $col
     * @param $row
     * @param $value
     * @throws Exception
     */
    private function setCellValueString(Worksheet $ws, $col, $row, $value)
    {
        $ws->getCell($col . $row)->setValueExplicit($value,PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    }

    /**
     * @param Worksheet $ws
     * @param $col
     * @param $row
     * @param $format
     * @throws Exception
     */
    private function setCellFormat(Worksheet $ws,$col,$row,$format)
    {
        $ws->getStyle($col . $row)->getNumberFormat()->setFormatCode($format);
    }

    /**
     * @param Worksheet $ws
     * @param $col
     * @param $row
     * @param $color
     * @throws Exception
     */
    private function setCellFillColor(Worksheet $ws,$col,$row,$color)
    {
        $ws->getStyle($col . $row)->applyFromArray([
            'fill' => [
                'type'  => PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => $color]
            ] 
        ]);
    }

    /**
     * @param Worksheet $ws
     * @param $col
     * @param $row
     * @param $dt
     * @param string $format
     * @throws Exception
     */
    private function setCellValueDate(Worksheet $ws,$col,$row,$dt,$format = 'ddd')
    {
        if (!$dt) return;
        
        $date = substr($dt, 0, 10);
        $dateValue = PhpSpreadsheet\Shared\Date::stringToExcel($date);

        $this->setCellFormat      ($ws,$col,$row,$format);
        $this->setCellValueNumeric($ws,$col,$row,$dateValue);
    }

    /**
     * @param Worksheet $ws
     * @param $col
     * @param $row
     * @param $dt
     * @param string $format
     * @throws Exception
     */
    private function setCellValueTime(Worksheet $ws,$col,$row,$dt,$format = '[$-409]h:mm AM/PM;@')
    {
        if (!$dt) return;
        
        $time = substr($dt,10);
        
        list($h, $m, $s) = explode(':', $time);
        $timeValue = $h / 24 + $m / 1440 + $s / 86400;

        $this->setCellFormat      ($ws,$col,$row,$format);
        $this->setCellValueNumeric($ws,$col,$row,$timeValue);
    }

    /**
     * @param Worksheet $ws
     * @param $col
     * @param $width
     */
    private function setColAutoSize(Worksheet $ws,$col)
    {
        $ws->getColumnDimension($col )->setAutoSize(true);
    }

    /**
     * @param Worksheet $ws
     * @param $col
     * @param $width
     */
    private function setColWidth(Worksheet $ws,$col,$width)
    {
        $ws->getColumnDimension($col )->setWidth($width);
    }

    /**
     * @param Worksheet $ws
     * @param $col
     * @param $alignment
     * @throws Exception
     */
    private function setColAlignment(Worksheet $ws,$col, $alignment = PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
    {
        $ws->getStyle($col)->getAlignment()->setHorizontal($alignment);

    }

    /**
     * @return Spreadsheet
     */
    private function createWorkBook()
    {
        // Not sure this is needed
        PhpSpreadsheet\Cell\Cell::setValueBinder(new PhpSpreadsheet\Cell\AdvancedValueBinder());

        $this->wb = $wb = new PhpSpreadsheet\Spreadsheet();

        return $wb;
    }

    /**
     * @return false|string
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    private function getContents()
    {
        $writer = PhpSpreadsheet\IOFactory::createWriter($this->wb, "Xlsx");

        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }

    /**
     * @return string
     */
    public function getFileExtension()
    {
        return 'xlsx';
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }
    
}