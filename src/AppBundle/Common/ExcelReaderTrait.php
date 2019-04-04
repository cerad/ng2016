<?php
namespace AppBundle\Common;

use PhpOffice\PhpSpreadsheet_Style_NumberFormat;

trait ExcelReaderTrait
{
    private function processTime($time)
    {
        return PhpSpreadsheet_Style_NumberFormat::toFormattedString($time, 'hh:mm:ss');
    }
    private function processDate($date)
    {
        return PhpSpreadsheet_Style_NumberFormat::toFormattedString($date, 'yyyy-MM-dd');
    }
}