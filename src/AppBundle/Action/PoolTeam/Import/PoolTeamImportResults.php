<?php
namespace AppBundle\Action\PoolTeam\Import;

class PoolTeamImportResults
{
    public $commit;
    public $fileName;
    
    public $totalCount   = 0;
    public $createdCount = 0;
    public $deletedCount = 0;
    public $updatedCount = 0;
    
    public $totalPoolTeams = [];
    public $createdPoolTeams = [];
    public $deletedPoolTeams = [];
    public $updatedPoolTeams = [];
    
    public $existingGames = [];
    
    public function __construct($poolTeams,$commit,$fileName)
    {
        $this->totalPoolTeams = $poolTeams;
        $this->commit         = $commit;
        $this->fileName       = $fileName;
    }
    public function addUpdatedPoolTeam($poolTeam)
    {
        $this->updatedPoolTeams[$poolTeam['poolTeamId']] = $poolTeam;
    }
    public function calcCounts()
    {
        $this->totalCount   = count($this->totalPoolTeams);
        $this->createdCount = count($this->createdPoolTeams);
        $this->deletedCount = count($this->deletedPoolTeams);
        $this->updatedCount = count($this->updatedPoolTeams);
        
    }
}
