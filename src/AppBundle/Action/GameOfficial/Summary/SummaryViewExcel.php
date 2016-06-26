<?php

namespace AppBundle\Action\GameOfficial\Summary;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SummaryViewExcel extends AbstractView2
{
    private $writer;

    public function __construct(SummaryWriterExcel $writer)
    {
        $this->writer = $writer;
    }
    public function __invoke(Request $request)
    {
        $writer = $this->writer;

        $response = new Response();

        $games      = $request->attributes->get('games');
        $regPersons = $request->attributes->get('regPersons');
        
        $response->setContent($writer->write($regPersons,$games));

        $response->headers->set('Content-Type', $writer->getContentType());

        $outFileName = 'Summary_' . date('Ymd_His') . '.' . $writer->getFileExtension();

        $response->headers->set('Content-Disposition', 'attachment; filename=' . $outFileName);

        return $response;
    }
}
