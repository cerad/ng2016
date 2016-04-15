<?php
namespace AppBundle\Action\Results\Sportsmanship\Calculator;

class SportsmanshipCalculator
{
    private $games;
    private $standings = array();
    
    public function getSportsmanshipStandings($games)
    {
        if (empty($games)) {
            return null;
        }
        
        $this->games = $games;
        $this->computeSportsmanshipStandings();
        
        return $this->standings;
    }
    
    // Comparison function
    // ref: http://php.net/manual/en/function.ksort.php
    private function sksort(&$array, $subkey, $sort_ascending=false)
    {
    
        if (count($array))
            $temp_array[key($array)] = array_shift($array);
    
        foreach($array as $key => $val){
            $offset = 0;
            $found = false;
            foreach($temp_array as $tmp_key => $tmp_val)
            {
                if(!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey]))
                {
                    $temp_array = array_merge(    (array)array_slice($temp_array,0,$offset),
                                                array($key => $val),
                                                array_slice($temp_array,$offset)
                                              );
                    $found = true;
                }
                $offset++;
            }
            if(!$found) $temp_array = array_merge($temp_array, array($key => $val));
        }
    
        if ($sort_ascending) $array = array_reverse($temp_array);
    
        else $array = $temp_array;
    }
    
    //Extracts the teams, totals and averages sp and ranks high to low
    private function computeSportsmanshipStandings()
    {
        //get the SP into an array keyed by team name
        foreach($this->games as $game) {
            //get each team report: sportsmanship
            foreach ($game['teams'] as $team) {
                
                $name = $team['name'];
                   
                $teamReport = $team['report'];
         
                $teamSportsmanship = isset($teamReport['sportsmanship']) ? $teamReport['sportsmanship'] : null;
         
                if (isset($this->standings[$name])) {
                    $this->standings[$name][] = $teamSportsmanship;
                } else {
                    $this->standings[$name] = array($teamSportsmanship);
                }
            }       

        }
        
        //compute total & average SP
        foreach($this->standings as &$team) {

            $total = 0;
            $gameCount = 0;

            foreach($team as $sp) {
                $total += $sp;
                $gameCount += 1;
            }

            $team['totalSP'] = $total;
            $team['gamesPlayed'] = $gameCount;
            $team['avgSP'] = !empty($gameCount) ? number_format($total / $gameCount,3) : null;
        }
        
        //sort by avg Sportsmanship
        $this->sksort($this->standings, "avgSP");

    }
    
}