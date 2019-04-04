<?php
namespace AppBundle\Common;

use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

trait ExcelReaderTrait
{
    private function processTime($time)
    {
        return NumberFormat::toFormattedString($time, 'hh:mm:ss');
    }
    private function processDate($date)
    {
        return NumberFormat::toFormattedString($date, 'yyyy-MM-dd');
    }
}