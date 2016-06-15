<?php

namespace AppBundle\Action\RegTeam;

use AppBundle\Action\AbstractView2;
use AppBundle\Action\AbstractExporter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RegTeamExcelWriter extends AbstractView2
{
    private $outFileName;
 
    private $exporter;

    public function __construct(AbstractExporter $exporter)
    {
        $this->outFileName =  'RegisteredTeams.' . date('Ymd_His') . '.' . $exporter->fileExtension;

        $this->exporter = $exporter;
        
    }
    public function __invoke(Request $request)
    {
        $exporter = $this->exporter;

        $divisions = $request->attributes->get('regTeamsByDivision');

        // generate the content
        $content = [];
        foreach ($divisions as $teams) {
            $divisionWS = $this->generateResponse($teams);
            $content = array_merge($content, $divisionWS);        
        }

        // generate the response
        $response = new Response();
        
        $response->setContent($exporter->export($content));

        $response->headers->set('Content-Type', $exporter->contentType);

        $response->headers->set('Content-Disposition', 'attachment; filename='. $this->outFileName);

        return $response;
    }
    protected function generateResponse($teams)
    {
        $response = [];
        
        //set the header labels
        $data =   array(
            array ('Team Number','Team Name','S-A-R','Soccerfest Points','Pool Team Key','QF Pool Team 1','SF Pool Team 2','FM Pool Team 3')
        );

        //set the data : game in each row
        foreach($teams as $t) {
            //if (!empty())
            $tArray = explode(' ', $t->regTeamName);
            $number = $tArray[0];
            $sar = $tArray[1];

            $data[] = array(
                $number,
                $t->regTeamName,
                $sar,
                $t->regTeamPoints,
                $t->poolTeamKey,
            );
    
        }

        //writes the data : division on each sheet
        $response[$t->division]['data'] = $data;
        $response[$t->division]['options']['freezePane'] = 'A2';
        $response[$t->division]['options']['horizontalAlignment'] = 'left';
        //lock all but 'Pool Team 1','QF Pool Team 1','SF Pool Team 2','FM Pool Team 3','Coach\'s Last Name', 'Team Region', 'Soccerfest Points'
        $response[$t->division]['options']['protection'] = array('pw' => '2016NG', 'unlocked' => array('D:H'));
            
        return $response;
    }
    
}
