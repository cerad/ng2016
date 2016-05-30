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
    
    private $databaseKey = 'database_name_ng2016games';

    private $conn;
    
    private $dumper;
    
    private $project = [];
    private $criteria = [];

    public function setUp()
    {
        parent::setup();
        
        $this->conn = $this->getConnection($this->databaseKey);
        
        $standingsCalculator = new ResultsStandingsCalculator();

        $this->resultsFinder = new ResultsFinder($this->conn, $standingsCalculator);
        
        $this->scheduleMedalRoundCalculator = new ScheduleMedalRoundCalculator();
        
        $this->dumper = new Dumper();

        
        $params = Yaml::parse(file_get_contents(__DIR__ . '/../../../../app/config/projects/AYSONationalGames2016.yml'));
        $params = $params['parameters']['app_project'];    
        $project = $params['info']['key'];

        $this->criteria['projects'] = [$project];
        $this->criteria['programs'] = ['Core'];
        $this->criteria['genders'] = ['B', 'G'];
        $this->criteria['ages'] = ['U10','U12','U14','U16','U19'];
        $this->criteria['group_names']  = ['A','B','C','D'];
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
        
        //4 pools
        $criteria['group_names']  = ['A','B','C','D'];
        
        $games = $this->resultsFinder->findPools($criteria);

        $qf = $this->scheduleMedalRoundCalculator->generateQuarterFinals($games);            

        $gamesFilename = __DIR__ . '/yamldata/' . $criteria['poolTypeKeys'][0] . count($criteria['group_names'] ) . '_games.dat';
        file_put_contents($gamesFilename, base64_encode(serialize($games)));

        $dataFilename = __DIR__ . '/yamldata/' . $criteria['poolTypeKeys'][0] . count($criteria['group_names'] ) . '_qf_data.yml';
        $this->generateYaml($qf, $dataFilename);

        //3 pools
        $criteria['group_names']  = ['A','B','C'];
        
        $games = $this->resultsFinder->findPools($criteria);

        $qf = $this->scheduleMedalRoundCalculator->generateQuarterFinals($games);            

        $gamesFilename = __DIR__ . '/yamldata/' . $criteria['poolTypeKeys'][0] . count($criteria['group_names'] ) . '_games.dat';
        file_put_contents($gamesFilename, base64_encode(serialize($games)));

        $dataFilename = __DIR__ . '/yamldata/' . $criteria['poolTypeKeys'][0] . count($criteria['group_names'] ) . '_qf_data.yml';
        $this->generateYaml($qf, $dataFilename);

        //2 pools
        $criteria['group_names']  = ['A','B'];
        
        $games = $this->resultsFinder->findPools($criteria);

        $qf = $this->scheduleMedalRoundCalculator->generateQuarterFinals($games);            

        $gamesFilename = __DIR__ . '/yamldata/' . $criteria['poolTypeKeys'][0] . count($criteria['group_names'] ) . '_games.dat';
        file_put_contents($gamesFilename, base64_encode(serialize($games)));

        $dataFilename = __DIR__ . '/yamldata/' . $criteria['poolTypeKeys'][0] . count($criteria['group_names'] ) . '_qf_data.yml';
        $this->generateYaml($qf, $dataFilename);

        //1 pool
        $criteria['group_names']  = ['A'];
        
        $games = $this->resultsFinder->findPools($criteria);

        $qf = $this->scheduleMedalRoundCalculator->generateQuarterFinals($games);            

        $gamesFilename = __DIR__ . '/yamldata/' . $criteria['poolTypeKeys'][0] . count($criteria['group_names'] ) . '_games.dat';
        file_put_contents($gamesFilename, base64_encode(serialize($games)));

        $dataFilename = __DIR__ . '/yamldata/' . $criteria['poolTypeKeys'][0] . count($criteria['group_names'] ) . '_qf_data.yml';
        $this->generateYaml($qf, $dataFilename);
        
    }
    
    public function testGenerateSemiFinals()
    {
        $this->criteria['poolTypeKeys'] = ['QF'];
        $this->criteria['group_names']  = ['A','B','C','D'];
        
        $games = $this->resultsFinder->findPools($this->criteria);

        $sf = $this->scheduleMedalRoundCalculator->generateSemiFinals($games);            

        $gamesFilename = __DIR__ . '/yamldata/qf_games.dat';
        file_put_contents($gamesFilename, base64_encode(serialize($games)));

        $dataFilename = __DIR__ . '/yamldata/sf_data.yml';
        $this->generateYaml($sf, $dataFilename);
    }
    
    public function testGenerateFinalMatches()
    {
        $this->criteria['poolTypeKeys'] = ['SF'];
        $this->criteria['group_names']  = ['A','B','C','D'];
        
        $games = $this->resultsFinder->findPools($this->criteria);

        $fm = $this->scheduleMedalRoundCalculator->generateFinals($games);            
        
        $gamesFilename = __DIR__ . '/yamldata/sf_games.dat';
        file_put_contents($gamesFilename, base64_encode(serialize($games)));

        $dataFilename = __DIR__ . '/yamldata/fm_data.yml';
        $this->generateYaml($fm, $dataFilename);

    }
    
}
