<?php
namespace AppBundle\Action\Schedule2019\MedalRound;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Action\AbstractView2;
use AppBundle\Action\AbstractExporter;

use AppBundle\Action\Results2019\ResultsStandingsCalculator;
use AppBundle\Action\Results2019\ResultsFinder;
use AppBundle\Action\Results2019\ResultsPool;

class ScheduleMedalRoundViewFile extends AbstractView2
{
    /** @var ScheduleMedalRoundCalculator **/
    private $scheduleMedalRoundCalculator;

    /** @var ResultsFinder **/
    private $resultsFinder;

    /** @var AbstractExporter **/
    private $exporter;
    
    private $mr;
    private $outFileName;
    private $pools;
    
    public function __construct(
        $mr,
        ScheduleMedalRoundCalculator $scheduleMedalRoundCalculator,
        ResultsFinder $resultsFinder,
        AbstractExporter $exporter)
    {                
        $this->mr = $mr;
        
        $this->exporter = $exporter;
        
        $this->scheduleMedalRoundCalculator = $scheduleMedalRoundCalculator;
        
        $this->resultsFinder = $resultsFinder;

    }

    public function __invoke(Request $request)
    {
        $this->project = $request->attributes->get('project');
        $criteria = $request->attributes->get('criteria');
        
        switch($this->mr) {
            case 'qf':
                $criteria['poolTypeKeys'] = ['PP'];
                $this->outFileName = date('Y-m-d_His') . '_QF_Schedule--For_Review.' . $this->exporter->fileExtension;
                break;
            case 'sf':
                $criteria['poolTypeKeys'] = ['QF']; 
                $this->outFileName = date('Y-m-d_His') . '_SF_Schedule--For_Review.' . $this->exporter->fileExtension;
                break;
            case 'fm':
                $criteria['poolTypeKeys'] = ['SF'];
                $this->outFileName = date('Y-m-d_His') . '_FM_Schedule--For_Review.' . $this->exporter->fileExtension;
                break;
        }

        switch($this->mr) {
            case 'qf':
                $games = $request->attributes->get('pools');
                break;
            case 'sf':
            case 'fm':
                $games = count($criteria) > 1 ? $this->resultsFinder->findPools($criteria) : [];
                break;
        }

        // generate the response
        $content = $this->generateResponse($games);
        
        $exporter = $this->exporter;

        $response = new Response();

        $response->setContent($exporter->export($content));

        $response->headers->set('Content-Type', $exporter->contentType);

        $response->headers->set('Content-Disposition', 'attachment; filename='. $this->outFileName);

        return $response;
    
    }
    
    protected function generateResponse($games)
    {
        switch($this->mr) {
            case 'qf':
                //$games = $pools for qf logic
                $data = $this->scheduleMedalRoundCalculator->generateQuarterFinals($games);                        
                break;
            
            case 'sf' :
                $data = $this->scheduleMedalRoundCalculator->generateSemiFinals($games);                
                break;
            
            case 'fm':
                $data = $this->scheduleMedalRoundCalculator->generateFinals($games);                
                break;
        }

        return $data;

    }
    
}
