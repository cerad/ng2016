<?php

namespace AppBundle\Action\Results2019;

use AppBundle\Action\Project\ProjectFactory;

class ResultsFinalCalculator
{
    protected $games;
    protected $medalRounds;

    /* ============================================================
     * Chops up games into medalRounds
     *
     */
    public function generateStandings(array $programs, array $games)
    {
        $this->games = $games;
        $this->medalRounds = [];

        foreach($games as $game) {
            $this->medalRoundGame($game);
        }
        
        foreach ($programs as $program){
            ksort($this->medalRounds[$program], SORT_STRING);            
        }

        return $this->medalRounds;
    }
    protected function medalRoundGame($game)
    {
        $homeTeam = $game['teams'][1];
        $awayTeam = $game['teams'][2];
        
        $tmp = explode(' ', $game['group_key']);
        $program = $tmp[1];

        if ( isset($homeTeam['report']['goalsScored']) and isset($awayTeam['report']['goalsScored']) ) {       
            if ($homeTeam['report']['goalsScored'] > $homeTeam['report']['goalsAllowed']) {
                if (strpos($homeTeam['group_slot'],'Win') > 0 ) {
                    $this->medalRounds[$program][$game['level_key']][1] = $homeTeam['name'];
                    $this->medalRounds[$program][$game['level_key']][2] = $awayTeam['name'];
                } else {
                    $this->medalRounds[$program][$game['level_key']][3] = $homeTeam['name'];
                    $this->medalRounds[$program][$game['level_key']][4] = $awayTeam['name'];
                }
            } else {
                if (strpos($homeTeam['group_slot'],'Win') > 0 ) {
                    $this->medalRounds[$program][$game['level_key']][1] = $awayTeam['name'];
                    $this->medalRounds[$program][$game['level_key']][2] = $homeTeam['name'];
                } else {
                    $this->medalRounds[$program][$game['level_key']][3] = $awayTeam['name'];
                    $this->medalRounds[$program][$game['level_key']][4] = $homeTeam['name'];
                }            
            }
        } else {
            if (strpos($homeTeam['group_slot'],'Win') > 0 ) {
                $this->medalRounds[$program][$game['level_key']][1] = '-';
                $this->medalRounds[$program][$game['level_key']][2] = '-';
            } else {
                $this->medalRounds[$program][$game['level_key']][3] = '-';
                $this->medalRounds[$program][$game['level_key']][4] = '-';
            }            
        }
    }
}
