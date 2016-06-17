<?php
namespace AppBundle\Common;

trait ExcelReaderTrait
{
    private function processTime($time)
    {
        return \PHPExcel_Style_NumberFormat::toFormattedString($time, 'hh:mm:ss');
    }
    private function processDate($date)
    {
        return \PHPExcel_Style_NumberFormat::toFormattedString($date, 'yyyy-MM-dd');
    }
}