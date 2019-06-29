<?php
namespace AppBundle\Action\Results;

class ResultsSportsmanshipCalculator
{
    public function getSportsmanshipStandings($divisionGames)
    {
        if (empty($divisionGames)) {
            return null;
        }
        
        $standings = null;
        $gamesProcessed = [];

        //get the SP into an array keyed by team name
        foreach($divisionGames as $poolGames) {

            foreach ($poolGames as $games) {

                $gameId = $games->gameId;
                if (!isset($gamesProcessed[$gameId])) {

                    $gamesProcessed[$gameId] = true;

                    //get each team report: sportsmanship
                    $teams = $games->getTeams();
                    foreach ($teams as $team) {
                        if (!is_null($team->results)) {
                            $name = $team->regTeamName;
                            $teamSportsmanship = $team->sportsmanship;
                            if (isset($standings[$name])) {
                                $standings[$name][] = $teamSportsmanship;
                            } else {
                                $standings[$name] = array($teamSportsmanship);
                            }
                        }
                    }
//                } else {
//                    dump($gamesProcessed);
                }
            }
        }

        if(is_null($standings)) return $standings;

        //compute total & average SP
        foreach($standings as &$team) {

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
        $this->sksort($standings, "avgSP");
        
        return $standings;
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
}
