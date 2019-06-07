<?php

namespace AppBundle\Action\RegTeam\Export;

use AppBundle\Action\AbstractView2;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RegTeamExportViewExcel extends AbstractView2
{
    private $writer;

    public function __construct(RegTeamExportWriterExcel $writer)
    {
        $this->writer = $writer;
    }
    public function __invoke(Request $request)
    {
        $writer = $this->writer;

        $response = new Response();

        $regTeams = $request->attributes->get('regTeams');
        $program = $request->attributes->get('program');
        if(!isset($program[0])) {
            $prefix = '';
        } else {
            $prefix = $program[0].'_';
        }

        $response->setContent($writer->write($regTeams));

        $response->headers->set('Content-Type', $writer->getContentType());

        $outFileName = $prefix.'RegTeams2018_' . date('Ymd_His') . '.' . $writer->getFileExtension();

        $response->headers->set('Content-Disposition', 'attachment; filename=' . $outFileName);

        return $response;
    }
}
