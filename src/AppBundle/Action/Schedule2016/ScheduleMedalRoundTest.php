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
        $games = unserialize(base64_decode(file_get_contents(__DIR__ . '/yamldata/pp1_games.dat')));

        $matches = $this->scheduleMedalRoundCalculator->generateQuarterFinals($games);      
        $matches = $matches['Medal Round QF']['data'];
        
        $this->assertCount(250, $matches);
        $this->assertArrayHasKey('AYSO_U10B_Core',$matches);
        $this->assertCount(6, $matches['AYSO_U10B_Core']); //limited to 6 with ng2014 data

        /* first place */
        $this->assertArrayHasKey('#01 10-W-0068 Caron', $matches['AYSO_U10B_Core']);
        $this->assertArrayHasKey('QF',$matches['AYSO_U10B_Core']['#01 10-W-0068 Caron']);
        $this->assertEquals('QF:1:A 1st',$matches['AYSO_U10B_Core']['#01 10-W-0068 Caron']['QF']);

        /* sixth place */
        $this->assertArrayHasKey('#16 12-D-0310 Ceja', $matches['AYSO_U10B_Core']);
        $this->assertArrayHasKey('QF',$matches['AYSO_U10B_Core']['#16 12-D-0310 Ceja']);
        $this->assertEquals('QF:4:A 6th',$matches['AYSO_U10B_Core']['#16 12-D-0310 Ceja']['QF']);

        /* two Pools */
        $games = unserialize(base64_decode(file_get_contents(__DIR__ . '/yamldata/pp2_games.dat')));

        $matches = $this->scheduleMedalRoundCalculator->generateQuarterFinals($games);      
        $matches = $matches['Medal Round QF']['data'];

        $this->assertCount(10, $matches);
        $this->assertArrayHasKey('AYSO_U12B_Core',$matches);
        $this->assertCount(12, $matches['AYSO_U12B_Core']);

        /* QF1: home */
        $this->assertArrayHasKey('#15 01-C-0088 Nord', $matches['AYSO_U12B_Core']);
        $this->assertArrayHasKey('QF',$matches['AYSO_U12B_Core']['#15 01-C-0088 Nord']);
        $this->assertEquals('QF:1:A 1st',$matches['AYSO_U12B_Core']['#15 01-C-0088 Nord']['QF']);

        /* QF4: away */
        $this->assertArrayHasKey('#06 08-C-0158 Feleo', $matches['AYSO_U12B_Core']);
        $this->assertArrayHasKey('QF',$matches['AYSO_U12B_Core']['#06 08-C-0158 Feleo']);
        $this->assertEquals('QF:4:A 4th',$matches['AYSO_U12B_Core']['#06 08-C-0158 Feleo']['QF']);

        /* three Pools */
        $games = unserialize(base64_decode(file_get_contents(__DIR__ . '/yamldata/pp3_games.dat')));

        $matches = $this->scheduleMedalRoundCalculator->generateQuarterFinals($games);      
        $matches = $matches['Medal Round QF']['data'];

        $this->assertCount(10, $matches);
        $this->assertArrayHasKey('AYSO_U14B_Core',$matches);
        $this->assertCount(12, $matches['AYSO_U14B_Core']);

        /* QF1: home */
        $this->assertArrayHasKey('#14 01-P-0019 Castillo', $matches['AYSO_U14B_Core']);
        $this->assertArrayHasKey('QF',$matches['AYSO_U14B_Core']['#14 01-P-0019 Castillo']);
        $this->assertEquals('QF:1:A 1st',$matches['AYSO_U14B_Core']['#14 01-P-0019 Castillo']['QF']);
        
        /* QF4: away */
        $this->assertArrayHasKey('#05 01-G-0065 Morton', $matches['AYSO_U14B_Core']);
        $this->assertArrayHasKey('QF',$matches['AYSO_U14B_Core']['#05 01-G-0065 Morton']);
        $this->assertEquals('QF:4:A 2nd',$matches['AYSO_U14B_Core']['#05 01-G-0065 Morton']['QF']);

        /* four Pools */
        $games = unserialize(base64_decode(file_get_contents(__DIR__ . '/yamldata/pp4_games.dat')));

        $matches = $this->scheduleMedalRoundCalculator->generateQuarterFinals($games);      
        $matches = $matches['Medal Round QF']['data'];
      
        $this->assertCount(10, $matches);
        $this->assertArrayHasKey('AYSO_U19G_Core',$matches);
        $this->assertCount(24, $matches['AYSO_U19G_Core']);

        /* QF1: home */
        $this->assertArrayHasKey('#19 01-R-0544 Kane', $matches['AYSO_U19G_Core']);
        $this->assertArrayHasKey('QF',$matches['AYSO_U19G_Core']['#19 01-R-0544 Kane']);
        $this->assertEquals('QF:1:A 1st',$matches['AYSO_U19G_Core']['#19 01-R-0544 Kane']['QF']);
        
        /* QF4: away */
        $this->assertArrayHasKey('#14 11-Z-0024 Jackson', $matches['AYSO_U19G_Core']);
        $this->assertArrayHasKey('QF',$matches['AYSO_U19G_Core']['#14 11-Z-0024 Jackson']);
        $this->assertEquals('QF:4:B 2nd',$matches['AYSO_U19G_Core']['#14 11-Z-0024 Jackson']['QF']);
    }
    
    public function testGenerateSemiFinals()
    {
        $games = unserialize(base64_decode(file_get_contents(__DIR__ . '/yamldata/qf_games.dat')));
//var_dump($games);
//var_dump(serialize($games));

        $matches = $this->scheduleMedalRoundCalculator->generateSemiFinals($games);
        $matches = $matches['Medal Round QF']['data'];
//var_dump($matches);die();

        $this->assertCount(72, $matches);
        $this->assertArrayHasKey('AYSO_U19B_Core',$matches);
        $this->assertCount(8, $matches['AYSO_U19B_Core']);

        /* QF1: home */
        $this->assertArrayHasKey('#10 02-B-0145 Kenny', $matches['AYSO_U19B_Core']);
        $this->assertArrayHasKey('SF',$matches['AYSO_U19B_Core']['#10 02-B-0145 Kenny']);
        $this->assertEquals('SF:5:QF 1 Win',$matches['AYSO_U19B_Core']['#10 02-B-0145 Kenny']['SF']);        

        /* QF4: away */
        $this->assertArrayHasKey('#20 01-H-0080 Cuevas', $matches['AYSO_U19B_Core']);
        $this->assertArrayHasKey('SF',$matches['AYSO_U19B_Core']['#20 01-H-0080 Cuevas']);
        $this->assertEquals('SF:9:QF 2 Run',$matches['AYSO_U19B_Core']['#20 01-H-0080 Cuevas']['SF']);        

    }
    
    public function testGenerateFinalMatches()
    {
        $games = unserialize(base64_decode(file_get_contents(__DIR__ . '/yamldata/sf_games.dat')));

        $matches = $this->scheduleMedalRoundCalculator->generateFinals($games);
        $matches = $matches['Medal Round QF']['data'];

        $this->assertCount(72, $matches);
        $this->assertArrayHasKey('AYSO_U19G_Core',$matches);
        $this->assertCount(8, $matches['AYSO_U19G_Core']);

        /* QF1: home */
        $this->assertArrayHasKey('#01 14-I-0345 Rodas', $matches['AYSO_U19G_Core']);
        $this->assertArrayHasKey('FM',$matches['AYSO_U19G_Core']['#01 14-I-0345 Rodas']);
        $this->assertEquals('FM:7:SF 1 Win',$matches['AYSO_U19G_Core']['#01 14-I-0345 Rodas']['FM']);        

        /* QF4: away */
        $this->assertArrayHasKey('#14 11-Z-0024 Jackson', $matches['AYSO_U19G_Core']);
        $this->assertArrayHasKey('FM',$matches['AYSO_U19G_Core']['#14 11-Z-0024 Jackson']);
        $this->assertEquals('FM:12:SF 4 Run',$matches['AYSO_U19G_Core']['#14 11-Z-0024 Jackson']['FM']);        
        
    }

}
