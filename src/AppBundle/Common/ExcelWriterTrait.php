<?php
namespace AppBundle\Common;

<<<<<<< HEAD
use PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use \PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
=======
use PhpOffice\PhpSpreadsheet;
>>>>>>> ng2019x2
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Trait ExcelWriterTrait
 * @package AppBundle\Common
 */
trait ExcelWriterTrait
{
<<<<<<< HEAD
    /** @var Spreadsheet */
    private $ws;
=======
    /** @var PhpSpreadsheet\Spreadsheet */
    private $wb;
>>>>>>> ng2019x2

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
<<<<<<< HEAD

    /**
     * @param Worksheet $ws
     * @param $col
     * @param $row
     * @param $value
     * @throws Exception
     */
    private function setCellValueNumeric(Worksheet $ws,$col,$row,$value)
    {
        $ws->getCell($col . $row)->setValueExplicit($value,DataType::TYPE_NUMERIC);
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
        $ws->getCell($col . $row)->setValueExplicit($value,DataType::TYPE_STRING);
    }

    /**
     * @param Worksheet $ws
     * @param $col
     * @param $row
     * @param $format
     * @throws Exception
     */
=======
    private function setCellValueNumeric(Worksheet $ws,$col,$row,$value)
    {
        $ws->getCell($col . $row)->setValueExplicit($value,PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
    }
    private function setCellValueString(Worksheet $ws, $col, $row, $value)
    {
        $ws->getCell($col . $row)->setValueExplicit($value,PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    }
>>>>>>> ng2019x2
    private function setCellFormat(Worksheet $ws,$col,$row,$format)
    {
        $ws->getStyle($col . $row)->getNumberFormat()->setFormatCode($format);
    }
<<<<<<< HEAD

    /**
     * @param Worksheet $ws
     * @param $col
     * @param $row
     * @param $color
     * @throws Exception
     */
=======
>>>>>>> ng2019x2
    private function setCellFillColor(Worksheet $ws,$col,$row,$color)
    {
        $ws->getStyle($col . $row)->applyFromArray([
            'fill' => [
<<<<<<< HEAD
                'type'  => Fill::FILL_SOLID,
=======
                'type'  => PhpSpreadsheet\Style\Fill::FILL_SOLID,
>>>>>>> ng2019x2
                'color' => ['rgb' => $color]
            ] 
        ]);
    }
<<<<<<< HEAD

    /**
     * @param Worksheet $ws
     * @param $col
     * @param $row
     * @param $dt
     * @param string $format
     * @throws Exception
     */
=======
>>>>>>> ng2019x2
    private function setCellValueDate(Worksheet $ws,$col,$row,$dt,$format = 'ddd')
    {
        if (!$dt) return;
        
        $date = substr($dt, 0, 10);
<<<<<<< HEAD
        $dateValue = Date::stringToExcel($date);
=======
        $dateValue = PhpSpreadsheet\Shared\Date::stringToExcel($date);
>>>>>>> ng2019x2
        
        $this->setCellFormat      ($ws,$col,$row,$format);
        $this->setCellValueNumeric($ws,$col,$row,$dateValue);
    }
<<<<<<< HEAD

    /**
     * @param Worksheet $ws
     * @param $col
     * @param $row
     * @param $dt
     * @param string $format
     * @throws Exception
     */
=======
>>>>>>> ng2019x2
    private function setCellValueTime(Worksheet $ws,$col,$row,$dt,$format = '[$-409]h:mm AM/PM;@')
    {
        if (!$dt) return;
        
        $time = substr($dt,10);
        
        list($h, $m, $s) = explode(':', $time);
        $timeValue = $h / 24 + $m / 1440 + $s / 86400;

        $this->setCellFormat      ($ws,$col,$row,$format);
        $this->setCellValueNumeric($ws,$col,$row,$timeValue);
    }
<<<<<<< HEAD


    /**
     * @param Worksheet $ws
     * @param $col
     * @param $width
     */
=======
>>>>>>> ng2019x2
    private function setColWidth(Worksheet $ws,$col,$width)
    {
        $ws->getColumnDimension($col )->setWidth($width);
    }
<<<<<<< HEAD

    /**
     * @param Worksheet $ws
     * @param $col
     * @throws Exception
     */
    private function setColAlignCenter(Worksheet $ws,$col)
    {
        $ws->getStyle($col)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
=======
    private function setColAlignCenter(Worksheet $ws,$col)
    {
        $ws->getStyle($col)->getAlignment()->setHorizontal(PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
>>>>>>> ng2019x2
    }

    /**
     * @return Spreadsheet
     */
    private function createWorkBook()
    {
        // Not sure this is needed
<<<<<<< HEAD
        Cell::setValueBinder(new AdvancedValueBinder());

        $this->ws = $ws = new Spreadsheet();
=======
        PhpSpreadsheet\Cell\Cell::setValueBinder(new PhpSpreadsheet\Cell\AdvancedValueBinder());

        $this->wb = $wb = new PhpSpreadsheet\Spreadsheet();
>>>>>>> ng2019x2
        
        return $ws;
    }

    /**
     * @return false|string
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    private function getContents()
    {
<<<<<<< HEAD
        $writer = IOFactory::createWriter($this->ws, "Xlsx");
=======
        $writer = PhpSpreadsheet\IOFactory::createWriter($this->wb, "Xlsx");
>>>>>>> ng2019x2
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