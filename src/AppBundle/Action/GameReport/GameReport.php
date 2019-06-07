<?php
namespace AppBundle\Action\GameReport;

use DateTime;
use InvalidArgumentException;

/**
 * @property-read GameReportTeam homeTeam
 * @property-read GameReportTeam awayTeam
 * 
 * @property-read string dow
 * @property-read string time
 */
class GameReport
{
    public $gameId;
    public $projectId;
    public $gameNumber;
    
    public $fieldName;
    public $venueName;
    public $start;
    public $finish;
    public $state  = 'Pending';
    public $status = 'Normal';

    public $reportText  = null;
    public $reportState = 'Initial';
    
    /** @var GameReportTeam[] */
    public $teams = [];

    private $keys = [
        'gameId'     => 'GameId',
        'projectId'  => 'ProjectId',
        'gameNumber' => 'integer',
        
        'fieldName'  => 'ProjectFieldName',
        'venueName'  => 'ProjectVenueName',
        'start'      => 'datetime',
        'finish'     => 'datetime',
        'state'      => 'string', // Pending, Published, InProgress, Played, Reported. Verified, Closed
        'status'     => 'string', // Normal, Played, Forfeited, Cancelled, Weather, Delayed, ToBeRescheduled
        
        'reportText'  => 'string',
        'reportState' => 'string',
    ];

    public function clearReport()
    {
        $this->reportText  = null;
        $this->reportState = 'Pending'; // Or initial based on start time
        $this->status      = 'Normal';
        
        $this->teams[1]->clearReport();
        $this->teams[2]->clearReport();
    }
    public function hasScores()
    {
        if ($this->teams[1]->pointsAllowed === null) return false;
        if ($this->teams[2]->pointsAllowed === null) return false;
        return true;
    }
    public function getReportState()
    {
        // Todo Calculate pending
        return $this->reportState;
    }
    public function __get($name)
    {
        switch($name) {

            case 'homeTeam': return $this->teams[1];
            case 'awayTeam': return $this->teams[2];

            case 'dow':
                $start = DateTime::createFromFormat('Y-m-d H:i:s',$this->start);
                return $start ? $start->format('D') : '???';
            
            case 'time':
                $start = DateTime::createFromFormat('Y-m-d H:i:s',$this->start);
                return $start ? $start->format('g:i A') : '???';
        }
        throw new InvalidArgumentException('GameReport::__get ' . $name);
    }
    public function toUpdateArray()
    {
        $gameReportRow = [
            'gameId'      => $this->gameId,
            'state'       => $this->state,
            'status'      => $this->status,
            'reportText'  => $this->reportText,
            'reportState' => $this->reportState,
        ];
        $gameReportRow['teams'] = [];
        foreach($this->teams as $slot => $gameTeam) {
            $gameReportRow['teams'][$slot] = $gameTeam->toUpdateArray();
        }
        return $gameReportRow;
    }
    /** 
     * @param  array $data
     * @return GameReport
     */
    static public function createFromArray($data)
    {
        $gameReport = new self();
        
        foreach($gameReport->keys as $key => $type) {
            if (isset($data[$key])) {
                $gameReport->$key = ($type === 'integer') ? (integer)$data[$key] : $data[$key];
            }
            else if (array_key_exists($key,$data)) {
                $gameReport->$key = $data[$key]; // To allow setting null values
            }
        }
        foreach($data['teams'] as $teamData) {
            $gameReport->teams[$teamData['slot']] = GameReportTeam::createFromArray($teamData);
        }
        return $gameReport;
    }
}