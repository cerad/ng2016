<?php

namespace AppBundle\Action\Schedule2019\Assignor;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ScheduleAssignorViewExcel extends AbstractView2
{
    private $writer;

    public function __construct(ScheduleAssignorWriterExcel $writer)
    {
        $this->writer = $writer;
    }
    public function __invoke(Request $request)
    {
        $writer = $this->writer;

        $response = new Response();

        $games = $request->attributes->get('games');

        $response->setContent($writer->write($games));

        $response->headers->set('Content-Type', $writer->getContentType());

        $outFileName = 'ScheduleAssignor2016_' . date('Ymd_His') . '.' . $writer->getFileExtension();

        $response->headers->set('Content-Disposition', 'attachment; filename=' . $outFileName);

        return $response;
    }
}
