<?php
namespace AppBundle\Action\Game\ImportAffinitySchedule;

class ImportAffinityScheduleGameResults
{
    public $commit;
    public $fileName;
    
    public $totalCount   = 0;
    public $createdCount = 0;
    public $deletedCount = 0;
    public $updatedCount = 0;
    
    public $totalGames = [];
    public $createdGames = [];
    public $deletedGames = [];
    public $updatedGames = [];
    
    public $invalidPoolTeamIds = [];
    public $invalidPoolTeamIdsCount;
    
    public function __construct($games,$commit,$fileName)
    {
        $this->games    = $games;
        $this->commit   = $commit;
        $this->fileName = $fileName;
    }
    public function addUpdatedGame($game)
    {
        $this->updatedGames[$game['gameId']] = $game;
    }
    public function calcCounts()
    {
        $this->totalCount = count($this->games);
        $this->createdCount = count($this->createdGames);
        $this->deletedCount = count($this->deletedGames);
        $this->updatedCount = count($this->updatedGames);
        
        $this->invalidPoolTeamIdsCount = count($this->invalidPoolTeamIds);
    }
}