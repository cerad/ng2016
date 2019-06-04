<?php
namespace AppBundle\Common;

use PhpOffice\PhpSpreadsheet;

trait ExcelReaderTrait
{
    private function processTime($time)
    {
        return PhpSpreadsheet\Style\NumberFormat::toFormattedString($time, 'hh:mm:ss');
    }
    private function processDate($date)
    {
        return PhpSpreadsheet\Style\NumberFormat::toFormattedString($date, 'yyyy-MM-dd');
    }
}