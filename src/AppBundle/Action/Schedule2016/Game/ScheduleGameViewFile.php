<?php

namespace AppBundle\Action\Schedule2016\Game;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ScheduleGameViewFile extends AbstractView2
{
    private $writer;
    
    public function __construct(ScheduleGameWriterExcel $writer)
    {
        $this->writer = $writer;
    }
    public function __invoke(Request $request)
    {
        $games = $request->attributes->get('games');

        $response = new Response();

        $writer = $this->writer;

        $response->setContent($writer->write($games));

        $response->headers->set('Content-Type', $writer->getContentType());

        $outFileName =  'GameSchedule.' . '_' . date('Ymd_His') . '.' . $writer->getFileExtension();

        $response->headers->set('Content-Disposition', 'attachment; filename='. $outFileName);

        return $response;
    }
}
