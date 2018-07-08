<?php
namespace AppBundle\Action\RegTeam\Import;

class RegTeamImportResults
{
    public $commit;
    public $fileName;
    
    public $totalCount   = 0;
    public $createdCount = 0;
    public $deletedCount = 0;
    public $updatedCount = 0;
    
    public $totalRegTeams = [];
    public $createdRegTeams = [];
    public $deletedRegTeams = [];
    public $updatedRegTeams = [];
    
    public $updatedPoolTeams = [];
    public $updatedPoolTeamCount = 0;
    
    public function __construct($regTeams,$commit,$fileName)
    {
        $this->totalRegTeams = $regTeams;
        $this->commit        = $commit;
        $this->fileName      = $fileName;
    }
    public function addUpdatedRegTeam($regTeam)
    {
        $this->updatedRegTeams[$regTeam['regTeamKey']] = $regTeam;
    }
    public function addUpdatedPoolTeam($poolTeam)
    {
        $this->updatedPoolTeams[$poolTeam['poolTeamKey']] = $poolTeam;
    }
    public function calcCounts()
    {
        $this->totalCount   = count($this->totalRegTeams);
        $this->createdCount = count($this->createdRegTeams);
        $this->deletedCount = count($this->deletedRegTeams);
        $this->updatedCount = count($this->updatedRegTeams);
        
        $this->updatedPoolTeamCount = count($this->updatedPoolTeams);
        
    }
}
