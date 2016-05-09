<?php
namespace AppBundle\Action\GameReport2016;

/**
 */
class GameReportTeamMisconduct
{
    public $playerWarnings, $playerEjections;
    public $coachWarnings,  $coachEjections;
    public $benchWarnings,  $benchEjections;
    public $specWarnings,   $specEjections;
    
    private $keys = [
        'playerWarnings'  => 'integer',
        'coachWarnings'   => 'integer',
        'benchWarnings'   => 'integer',
        'specWarnings'    => 'integer',

        'playerEjections' => 'integer',
        'coachEjections'  => 'integer',
        'benchEjections'  => 'integer',
        'specEjections'   => 'integer',
    ];
    public function clearReport()
    {
        $this->playerWarnings = $this->playerEjections = null;
        $this->coachWarnings  = $this->coachEjections  = null;
        $this->benchWarnings  = $this->benchEjections  = null;
        $this->specWarnings   = $this->specEjections   = null;
    }
    public function __get($name)
    {
        switch($name) {
            
        }
        throw new \InvalidArgumentException('GameReportTeamMisconduct::__get ' . $name);
    }
    public function toUpdateArray()
    {
        $item = [];
        foreach(array_keys($this->keys) as $key) {
            $data = $this->$key;
            if (isset($data)) {
                $item[$key] = $data;
            }
        }
        return $item;
    }
    /**
     * @param  array $data
     * @return GameReportTeamMisconduct
     */
    static public function createFromArray($data)
    {
        $gameReportTeamMisconduct = new self();

        foreach($gameReportTeamMisconduct->keys as $key => $type) {
            if (isset($data[$key])) {
                $gameReportTeamMisconduct->$key = ($type === 'integer') ? (integer)$data[$key] : $data[$key];
            }
            else if (array_key_exists($key,$data)) {
                $gameReportTeamMisconduct->$key = $data[$key]; // To allow setting null values
            }
        }
        return $gameReportTeamMisconduct;
    }
}