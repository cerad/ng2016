<?php
namespace AppBundle\Action\Results2016;

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
            $this->conn = $this->getConnection('database_name_ng2016games');
        }
        
        return $this->resultsFinder = new ResultsFinder($this->conn);
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

        $poolTeams = $pool->getPoolTeams();
        $this->assertCount(6,$poolTeams);

        /** @var ResultsPoolTeam $poolTeam */
        $poolTeam = array_values($poolTeams)[3];
        $this->assertEquals('U14B-Core-PP-B4',$poolTeam->poolTeamKey);

        $games = $pool->getGames();
        $this->assertCount(24,$games);
    }
}