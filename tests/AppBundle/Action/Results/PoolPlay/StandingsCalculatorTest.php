<?php
namespace Tests\AppBundle\Action\Results\PoolPlay;

use AppBundle\Action\Results\PoolPlay\Calculator\StandingsCalculator;

use Cerad\Bundle\ProjectBundle\ProjectFactory;

class StandingsCalculatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ProjectFactory */
    private $projectFactory;

    /** @var  StandingsCalculator */
    private $standingsCalculator;

    public function setUp()
    {
        parent::setUp();
        $this->projectFactory      = new ProjectFactory();
        $this->standingsCalculator = new StandingsCalculator($this->projectFactory);
    }
    public function testGeneratePools()
    {
        $games = [];

        $poolKey = 'U14B Core PP B';

        $report1 = $this->projectFactory->createProjectGameTeamReport();
        $report2 = $this->projectFactory->createProjectGameTeamReport();

        $report1 = array_merge($report1,[
            'goalsScored'  => 3,
            'goalsAllowed' => 1,
            'pointsEarned' => 9,
            'pointsMinus'  => 0,
        ]);
        $report2 = array_merge($report2,[
            'goalsScored'  => 1,
            'goalsAllowed' => 3,
            'pointsEarned' => 1,
            'pointsMinus'  => 0,
        ]);

        $games[] = [
            'id'         => 100,
            'group_key'  =>  $poolKey,
            'group_type' => 'PP',
            'teams' => [
                1 => [
                    'id'         => 200,
                    'slot'       => 1,
                    'role'       => 'Home',
                    'group_slot' => 'B1',
                    'report'     => $report1,
                    'points'     => 6,
                ],
                2 => [
                    'id'         => 201,
                    'slot'       => 2,
                    'role'       => 'Away',
                    'group_slot' => 'B2',
                    'report'     => $report2,
                    'points'     => 6,
                ]
            ]
        ];

        $pools = $this->standingsCalculator->generatePools($games);

        $this->assertTrue(isset($pools[$poolKey]['games'][100]));

        $this->assertEquals(2,count($pools[$poolKey]['teams']));

        // The pool team report
        $this->assertEquals(201,$pools[$poolKey]['teams'][1]['team']['id']);
        $this->assertEquals(  3,$pools[$poolKey]['teams'][1]['goalsAllowed']);
        $this->assertEquals( -2,$pools[$poolKey]['teams'][1]['goalDifferential']);
    }
}