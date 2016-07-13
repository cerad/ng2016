<?php
namespace AppBundle\Action\Schedule2016\MedalRound;

use PHPUnit_Framework_TestCase;

class ScheduleMedalRoundCalculatorTest extends PHPUnit_Framework_TestCase
{
    private $testDataDir;

    /** @var  ScheduleMedalRoundCalculator */
    private $scheduleMedalRoundCalculator;
    
    public function setUp()
    {
        parent::setUp();
        
        $this->scheduleMedalRoundCalculator = new ScheduleMedalRoundCalculator();

        // Linux directory names are case sensitive
        $this->testDataDir = __DIR__ . '/TestData';
    }
    
    public function testGenerateQuarterFinals()
    {
        /* one pool */
        $games = unserialize(base64_decode(file_get_contents($this->testDataDir . '/PP1_games.dat')));

        $matches = $this->scheduleMedalRoundCalculator->generateQuarterFinals($games);      
        $matches = $matches['Medal Round QF']['data'];

        $this->assertCount(70, $matches); // 6 teams x 10 pools + 10 blank separator rows in $data
        
        /* U10B A 1st */
        $this->assertEquals("U10BCorePPA1", $matches[1][0]);
        $this->assertEquals('QF1X',$matches[1][4]);

        /* U10B A 6th */
        $this->assertEquals("U10BCorePPA6", $matches[6][0]);
        $this->assertEquals('QF4Y',$matches[6][4]);

        /* two Pools */
        $games = unserialize(base64_decode(file_get_contents($this->testDataDir . '/PP2_games.dat')));

        $matches = $this->scheduleMedalRoundCalculator->generateQuarterFinals($games);      
        $matches = $matches['Medal Round QF']['data'];

        $this->assertCount(130, $matches);  // 6 teams x 2 pools x 10 division + 10 blank separator rows in $data
    
        /* U10B A 1st */
        $this->assertEquals("U10BCorePPA1", $matches[1][0]);
        $this->assertEquals('U10BCoreQF1X',$matches[1][4]);

        /* U10B B 6th */
        $this->assertEquals('U10BCorePPB6',$matches[12][0]);
        $this->assertEquals('',$matches[12][4]);

        /* QF1: home */
        $this->assertEquals("U10BCorePPA1", $matches[1][0]);

        /* QF4: away */
        $this->assertEquals("U10BCoreQF4Y", $matches[4][4]);

        /* three Pools */
        $games = unserialize(base64_decode(file_get_contents($this->testDataDir . '/PP3_games.dat')));

        $matches = $this->scheduleMedalRoundCalculator->generateQuarterFinals($games);      
        $matches = $matches['Medal Round QF']['data'];

        $this->assertCount(190, $matches); // 6 teams x 3 pools x 10 division + 10 blank separator rows in $data

        /* U14G QF4: home */
        $this->assertEquals("U14GCorePPB1",$matches[102][0]);
        
        /* U16B QF4: home */
        $this->assertEquals("U16BCorePPC1",$matches[127][0]);

        /* four Pools */
        $games = unserialize(base64_decode(file_get_contents($this->testDataDir . '/PP4_games.dat')));

        $matches = $this->scheduleMedalRoundCalculator->generateQuarterFinals($games);      
        $matches = $matches['Medal Round QF']['data'];
      
        /* U14G: QF1: home */
        $this->assertEquals("U14GCorePPB1",$matches[126][0]);
        
        /* U14B: QF3: home */
        $this->assertEquals("U14BCorePPD3",$matches[115][0]);
    }
    
    public function testGenerateSemiFinals()
    {
        $games = unserialize(base64_decode(file_get_contents($this->testDataDir . '/qf_games.dat')));

        $matches = $this->scheduleMedalRoundCalculator->generateSemiFinals($games);
        $matches = $matches['Medal Round SF']['data'];

        $this->assertCount(90, $matches);
        $matches[19][0];
        /* SF:5:QF 1 Win */
        $this->assertEquals("U12BCoreQF1X",$matches[19][0]);

        /* SF:10:QF 3 Rup */
        $this->assertEquals("U19GCoreQF4Y",$matches[89][0]);

    }
    
    public function testGenerateFinalMatches()
    {
        $games = unserialize(base64_decode(file_get_contents($this->testDataDir . '/sf_games.dat')));

        $matches = $this->scheduleMedalRoundCalculator->generateFinals($games);
        $matches = $matches['Medal Round TF']['data'];

        $this->assertCount(90, $matches);

        /* FM:7:SF 2 Win */
        $this->assertEquals("U12BCoreSF2X",$matches[21][0]);

        /* FM:12:SF 4 Run */
        $this->assertEquals("U19BCoreSF3X",$matches[77][0]);
        
    }

}
