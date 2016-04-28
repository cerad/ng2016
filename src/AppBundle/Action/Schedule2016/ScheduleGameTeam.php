<?php
namespace AppBundle\Action\Schedule2016;

/**
 * @property-read string $projectKey
 * @property-read string $gameNumber
 * @property-read string $slot
 *
 */
class ScheduleGameTeam
{
    public $id;
    public $projectKey;
    public $gameNumber;
    public $slot;

    public $name;

    public $score;
    public $sportsmanship;
    public $misconduct;

    public $poolView;
    public $poolTypeView;
    public $poolTeamView;
    public $poolTeamSlotView;

    public $orgKey;

    private $keys = [

        'id'         => 'ProjectGameId',
        'projectKey' => 'ProjectId',
        'gameNumber' => 'integer',
        'slot'       => 'integer',

        'name' => 'string',

        'score'         => 'integer|null',
        'sportsmanship' => 'integer|null',
        'misconduct'    => 'array',

        'poolView'         => 'string',
        'poolTypeView'     => 'string',
        'poolTeamView'     => 'string',
        'poolTeamSlotView' => 'string',

        'orgKey' => 'PhysicalOrgId', // Could be part of project team
    ];

    public function __get($name)
    {
        switch($name) {
            
        }
        throw new \InvalidArgumentException('GameTeam::__get ' . $name);
    }

    /**
     * @param  array $data
     * @return ScheduleGameTeam
     */
    static public function fromArray($data)
    {
        $gameTeam = new ScheduleGameTeam();

        foreach(array_keys($gameTeam->keys) as $key) {
            if (isset($data[$key]) || array_key_exists($key,$data)) {
                $gameTeam->$key = $data[$key];
            }
        }
        return $gameTeam;
    }
}