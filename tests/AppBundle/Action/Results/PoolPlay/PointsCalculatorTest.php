<?php
namespace Tests\AppBundle\Action\Results\PoolPlay;

use AppBundle\Action\Results\PoolPlay\PointCalculator;

use AppBundle\Action\Results\PoolPlay\Calculator\PointsCalculator;

use Cerad\Bundle\ProjectBundle\ProjectFactory;

class PointsCalculatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ProjectFactory */
    private $projectFactory;

    /** @var  PointsCalculator */
    private $pointsCalculator;

    public function setUp()
    {
        parent::setUp();
        $this->projectFactory   = new ProjectFactory();
        $this->pointsCalculator = new PointsCalculator();
    }
    public function test1()
    {
        $report1 = $this->projectFactory->createProjectGameTeamReport();
        $report2 = $this->projectFactory->createProjectGameTeamReport();

        $report1 = array_merge($report1,[
            'goalsScored'  => 3,
        ]);
        $report2 = array_merge($report2,[
            'goalsScored'  => 1,
        ]);
        $team1 = [
            'score'  => null,
            'report' => $report1,
        ];
        $team2 = [
            'score'  => null,
            'report' => $report2,
        ];
        $game = [
            'teams' => [
                1 => $team1,
                2 => $team2,
            ]
        ];
        $game = $this->pointsCalculator->calcPointsForGame($game);

        $this->assertEquals(3,$game['teams'][1]['score']);
        $this->assertEquals(1,$game['teams'][2]['score']);

        $report1 = $game['teams'][1]['report'];
        $report2 = $game['teams'][2]['report'];

        $this->assertEquals(8,$report1['pointsEarned']);
        $this->assertEquals(0,$report2['pointsEarned']);

        $this->assertEquals(1,$report1['goalsAllowed']);
        $this->assertEquals(3,$report2['goalsAllowed']);
    }
}