<?php
namespace AppBundle\Action\Schedule2016;

use AppBundle\Action\Results2016\ResultsStandingsCalculator;
use AppBundle\Action\Results2016\ResultsFinder;

use AppBundle\Action\Schedule2016\ScheduleMedalRoundCalculator;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Dumper;

use AppBundle\Common\DatabaseTrait;

use PHPUnit_Framework_TestCase;

class ScheduleMedalRoundTest extends PHPUnit_Framework_TestCase
{
    use DatabaseTrait;

    /** @var  ResultsFinder */
    private $resultsFinder;

    /** @var  ScheduleMedalRoundCalculator */
    private $scheduleMedalRoundCalculator;
    
    private $databaseKey = 'database_name_ng2014games';

    private $conn;
    
    private $dumper;
    
    private $projectChoices;
    
    private $criteria = [];

    public function setUp()
    {
        parent::setup();
        
        $this->conn = $this->getConnection($this->databaseKey);
        
        $standingsCalculator = new ResultsStandingsCalculator();

        $this->resultsFinder = new ResultsFinder($this->conn, $standingsCalculator);
        
        $this->scheduleMedalRoundCalculator = new ScheduleMedalRoundCalculator();
        
        $this->dumper = new Dumper();
        
        $params = Yaml::parse(file_get_contents(__DIR__ . '/../../../../app/config/projects.yml'));
        $this->projectChoices = $params['parameters']['project_choices'];    

        $projectId = $this->getDefaultProjectId();
        
        $this->criteria['projects'] = [$projectId];
        $this->criteria['programs'] = ['Core'];
        $this->criteria['genders'] = ['B', 'G'];
        $this->criteria['ages'] = ['U10','U12','U14','U16','U19'];
    }
    private function generateYaml($data, $dataFilename)
    {
        $yaml = $this->dumper->dump($data, 3);
        file_put_contents($dataFilename, $yaml);                
    }    
    public function testGenerateQuarterFinals()
    {
        $criteria = $this->criteria;

        $criteria['poolTypeKeys'] = ['PP'];
        
        //1 pool
        $criteria['poolSlotViews']  = ['A'];

        $games = $this->resultsFinder->findPools($criteria);

        $qf = $this->scheduleMedalRoundCalculator->generateQuarterFinals($games);            

        $gamesFilename = __DIR__ . '/testdata/' . $criteria['poolTypeKeys'][0] . count($criteria['poolSlotViews'] ) . '_games.dat';
        file_put_contents($gamesFilename, base64_encode(serialize($games)));

        $dataFilename = __DIR__ . '/testdata/' . $criteria['poolTypeKeys'][0] . count($criteria['poolSlotViews'] ) . '_qf_data.yml';
        $this->generateYaml($qf, $dataFilename);
        
        //2 pools
        $criteria['poolSlotViews']  = ['A','B'];
        
        $games = $this->resultsFinder->findPools($criteria);

        $qf = $this->scheduleMedalRoundCalculator->generateQuarterFinals($games);            

        $gamesFilename = __DIR__ . '/testdata/' . $criteria['poolTypeKeys'][0] . count($criteria['poolSlotViews'] ) . '_games.dat';
        file_put_contents($gamesFilename, base64_encode(serialize($games)));

        $dataFilename = __DIR__ . '/testdata/' . $criteria['poolTypeKeys'][0] . count($criteria['poolSlotViews'] ) . '_qf_data.yml';
        $this->generateYaml($qf, $dataFilename);

        //3 pools
        $criteria['poolSlotViews']  = ['A','B','C'];
        
        $games = $this->resultsFinder->findPools($criteria);

        $qf = $this->scheduleMedalRoundCalculator->generateQuarterFinals($games);            

        $gamesFilename = __DIR__ . '/testdata/' . $criteria['poolTypeKeys'][0] . count($criteria['poolSlotViews'] ) . '_games.dat';
        file_put_contents($gamesFilename, base64_encode(serialize($games)));

        $dataFilename = __DIR__ . '/testdata/' . $criteria['poolTypeKeys'][0] . count($criteria['poolSlotViews'] ) . '_qf_data.yml';
        $this->generateYaml($qf, $dataFilename);

        //4 pools
        $criteria['poolSlotViews']  = ['A','B','C','D'];
        
        $games = $this->resultsFinder->findPools($criteria);

        $qf = $this->scheduleMedalRoundCalculator->generateQuarterFinals($games);            

        $gamesFilename = __DIR__ . '/testdata/' . $criteria['poolTypeKeys'][0] . count($criteria['poolSlotViews'] ) . '_games.dat';
        file_put_contents($gamesFilename, base64_encode(serialize($games)));

        $dataFilename = __DIR__ . '/testdata/' . $criteria['poolTypeKeys'][0] . count($criteria['poolSlotViews'] ) . '_qf_data.yml';
        $this->generateYaml($qf, $dataFilename);

    }
    
    public function testGenerateSemiFinals()
    {
        $this->criteria['poolTypeKeys'] = ['QF'];
        unset($this->criteria['poolSlotViews']);
        
        $games = $this->resultsFinder->findPools($this->criteria);
        $sf = $this->scheduleMedalRoundCalculator->generateSemiFinals($games);            

        $gamesFilename = __DIR__ . '/testdata/qf_games.dat';
        file_put_contents($gamesFilename, base64_encode(serialize($games)));

        $dataFilename = __DIR__ . '/testdata/sf_data.yml';
        $this->generateYaml($sf, $dataFilename);
    }
    
    public function testGenerateFinalMatches()
    {
        $this->criteria['poolTypeKeys'] = ['SF'];
        unset($this->criteria['poolSlotViews']);
        
        $games = $this->resultsFinder->findPools($this->criteria);

        $fm = $this->scheduleMedalRoundCalculator->generateFinals($games);            
        
        $gamesFilename = __DIR__ . '/testdata/sf_games.dat';
        file_put_contents($gamesFilename, base64_encode(serialize($games)));

        $dataFilename = __DIR__ . '/testdata/fm_data.yml';
        $this->generateYaml($fm, $dataFilename);

    }
    private function getDefaultProjectId()
    {
        return array_keys($this->projectChoices)[1]; //AYSONationalGames2014
    }    
    
}
