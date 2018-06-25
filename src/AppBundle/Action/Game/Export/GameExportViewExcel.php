<?php

namespace AppBundle\Action\Game\Export;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GameExportViewExcel extends AbstractView2
{
    private $writer;

    public function __construct(GameExportWriterExcel $writer)
    {
        $this->writer = $writer;
    }
    public function __invoke(Request $request)
    {
        $writer = $this->writer;

        $response = new Response();

        $games = $request->attributes->get('games');
        $program = $request->attributes->get('program');
        if(!isset($program[0])) {
            $prefix = '';
        } else {
            $prefix = $program[0].'_';
        }

        $response->setContent($writer->write($games));

        $response->headers->set('Content-Type', $writer->getContentType());

        $outFileName = $prefix.'GameSchedule_' . date('Ymd_His') . '.' . $writer->getFileExtension();

        $response->headers->set('Content-Disposition', 'attachment; filename=' . $outFileName);

        return $response;
    }
}
