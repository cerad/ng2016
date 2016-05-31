<?php
namespace AppBundle\Action\Schedule2016;

use AppBundle\Action\Schedule2016\ScheduleMedalRoundCalculator;

use Symfony\Component\Yaml\Yaml;

use PHPUnit_Framework_TestCase;

class ScheduleMedalRoundCalculatorTest extends PHPUnit_Framework_TestCase
{
    private $scheduleMedalRoundCalculator;
    
    public function setUp()
    {
        parent::setup();
        
        $this->scheduleMedalRoundCalculator = new ScheduleMedalRoundCalculator;
    }
    
    public function testGenerateQuarterFinals()
    {
        /* one pool */
        $games = unserialize(base64_decode(file_get_contents(__DIR__ . '/testdata/pp1_games.dat')));

        $matches = $this->scheduleMedalRoundCalculator->generateQuarterFinals($games);      
        $matches = $matches['Medal Round QF']['data'];

        $this->assertCount(70, $matches); // 6 teams x 10 pools + 10 blank separator rows in $data
        
        /* U10B A 1st */
        $this->assertEquals("#01 10-W-0068 Caron", $matches[1][1]);
        $this->assertEquals('QF:1:Home:A 1st',$matches[1][4]);

        /* U10B A 6th */
        $this->assertEquals("#16 12-D-0310 Ceja", $matches[6][1]);
        $this->assertEquals('QF:4:Away:A 6th',$matches[6][4]);

        /* two Pools */
        $games = unserialize(base64_decode(file_get_contents(__DIR__ . '/testdata/pp2_games.dat')));

        $matches = $this->scheduleMedalRoundCalculator->generateQuarterFinals($games);      
        $matches = $matches['Medal Round QF']['data'];

        $this->assertCount(130, $matches);  // 6 teams x 2 pools x 10 division + 10 blank separator rows in $data
    
        /* U10B A 1st */
        $this->assertEquals("#01 10-W-0068 Caron", $matches[1][1]);
        $this->assertEquals('QF:1:Home:A 1st',$matches[1][4]);

        /* U10B B 6th */
        $this->assertEquals('#19 01-S-0397 Burgess',$matches[12][1]);
        $this->assertEquals('',$matches[12][4]);

        /* QF1: home */
        $this->assertEquals("#01 10-W-0068 Caron", $matches[1][1]);

        /* QF4: away */
        $this->assertEquals("#13 01-H-0080 Schieldge", $matches[4][1]);

        /* three Pools */
        $games = unserialize(base64_decode(file_get_contents(__DIR__ . '/testdata/pp3_games.dat')));

        $matches = $this->scheduleMedalRoundCalculator->generateQuarterFinals($games);      
        $matches = $matches['Medal Round QF']['data'];

        $this->assertCount(190, $matches); // 6 teams x 3 pools x 10 division + 10 blank separator rows in $data

        /* U14G QF4: home */
        $this->assertEquals("#18 01-D-0018 Chen",$matches[102][1]);
        
        /* U16B QF4: home */
        $this->assertEquals("#21 01-C-0002 Joe",$matches[127][1]);

        /* four Pools */
        $games = unserialize(base64_decode(file_get_contents(__DIR__ . '/testdata/pp4_games.dat')));

        $matches = $this->scheduleMedalRoundCalculator->generateQuarterFinals($games);      
        $matches = $matches['Medal Round QF']['data'];
      
        $this->assertCount(250, $matches);// 6 teams x 4 pools x 10 division + 10 blank separator rows in $data

        /* U14G: QF1: home */
        $this->assertEquals("#11 01-B-0003 Knudsen",$matches[126][1]);
        
        /* U14B: QF3: home */
        $this->assertEquals("#15 01-U-0624 Nunez",$matches[115][1]);
    }
    
    public function testGenerateSemiFinals()
    {
        $games = unserialize(base64_decode(file_get_contents(__DIR__ . '/testdata/qf_games.dat')));

        $matches = $this->scheduleMedalRoundCalculator->generateSemiFinals($games);
        $matches = $matches['Medal Round SF']['data'];

        $this->assertCount(154, $matches);

        /* SF:5:QF 1 Win */
        $this->assertEquals("#15 01-C-0088 Nord",$matches[19][1]);        

        /* SF:10:QF 3 Rup */
        $this->assertEquals("#23 11-K-0143 Fisher",$matches[126][1]);        

    }
    
    public function testGenerateFinalMatches()
    {
        $games = unserialize(base64_decode(file_get_contents(__DIR__ . '/testdata/sf_games.dat')));

        $matches = $this->scheduleMedalRoundCalculator->generateFinals($games);
        $matches = $matches['Medal Round FM']['data'];

        $this->assertCount(154, $matches);

        /* FM:7:SF 2 Win */
        $this->assertEquals("#11 01-U-0215 Florez",$matches[21][1]);        

        /* FM:12:SF 4 Run */
        $this->assertEquals("#21 11-E-0094 Matewosian",$matches[77][1]);        
        
    }

}
