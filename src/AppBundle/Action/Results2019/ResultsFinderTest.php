<?php
namespace AppBundle\Action\Results2019;

use AppBundle\Common\DatabaseTrait;

class ResultsFinderTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTrait;
    
    private $projectId = 'AYSONationalGames2014';
    
    private $conn;
    
    private $resultsFinder;
    
    private function getResultsFinder()
    {
        if ($this->resultsFinder) {
            return $this->resultsFinder;
        }
        if (!$this->conn) {
            // Cheat and use a live database for now
            $this->conn = $this->getConnection('database_name_ng2019games');
        }
        $standingsCalculator = new ResultsStandingsCalculator();

        return $this->resultsFinder = new ResultsFinder($this->conn,$standingsCalculator);
    }
    public function testFind()
    {
        $resultsFinder = $this->getResultsFinder();

        $criteria = [
            'projectIds'   => [$this->projectId],
            'poolTypeKeys' => ['PP'],
            'programs'     => ['Core'],
            'divisions'    => ['U14B'],
        ];
        $pools = $resultsFinder->findPools($criteria);
        $this->assertCount(4,$pools);

        /** @var ResultsPool $pool */
        $pool = array_values($pools)[1];
        $this->assertEquals('U14B-Core-PP-B',$pool->poolKey);

        $poolTeams = $pool->getPoolTeamStandings();
        $this->assertCount(6,$poolTeams);

        $games = $pool->getGames();
        $this->assertCount(15,$games);

        /** @var ResultsPoolTeam $poolTeam */
        $poolTeam = array_values($poolTeams)[2];
        $this->assertEquals('U14B-Core-PP-B4',$poolTeam->poolTeamKey);

        $this->assertEquals(    32, $poolTeam->pointsEarned);
        $this->assertEquals(   198, $poolTeam->sportsmanship);
        $this->assertEquals('57.14',$poolTeam->winPercentView);

        $this->assertEquals(3,$poolTeam->standing);
        $this->assertEquals('#22 11-S-0116 Withrow',$poolTeam->regTeamName);

        //$standingsCalculator = new ResultsStandingsCalculator();
        //$poolTeamStandings = $standingsCalculator($pool);

        //foreach($poolTeamStandings as $poolTeam) {
        //  echo sprintf("%d %s %s %s\n",$poolTeam->standing,$poolTeam->poolTeamSlotView,$poolTeam->regTeamName,$poolTeam->winPercentView);
        //}
        //$poolTeam = $poolTeamStandings[2];
        //$this->assertEquals(3,$poolTeam->standing);
        //$this->assertEquals('#22 11-S-0116 Withrow',$poolTeam->regTeamName);

    }
}