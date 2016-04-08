<?php
namespace Tests\AppBundle\Action\Results\PoolPlay;

use AppBundle\Action\Results\PoolPlay\Calculator\PointsCalculator;

use AppBundle\Action\Project\ProjectFactory;

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
        $gameReport = $this->projectFactory->createProjectGameReport();

        $homeTeamReport = $this->projectFactory->createProjectGameTeamReport();
        $awayTeamReport = $this->projectFactory->createProjectGameTeamReport();

        $homeTeamReport['goalsScored']  = 3;
        $awayTeamReport['goalsScored']  = 1;

        $gameReport['teamReports'][1] = $homeTeamReport;
        $gameReport['teamReports'][2] = $awayTeamReport;

        $gameReport = $this->pointsCalculator->calcPointsForGameReport($gameReport);

        $homeTeamReport = $gameReport['teamReports'][1];
        $awayTeamReport = $gameReport['teamReports'][2];

        $this->assertEquals(8,$homeTeamReport['pointsEarned']);
        $this->assertEquals(0,$awayTeamReport['pointsEarned']);

        $this->assertEquals(1,$homeTeamReport['goalsAllowed']);
        $this->assertEquals(3,$awayTeamReport['goalsAllowed']);
    }
}