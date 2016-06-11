<?php
namespace AppBundle\Action\Schedule2016;

trait ScheduleControllerTrait
{
    protected function filterGames($games,$filter)
    {
        $filters = explode(',',$filter);
        $filtersTrimmed = [];
        foreach($filters as $filter) {
            $filter = trim($filter);
            if ($filter) {
                $filtersTrimmed[] = $filter;
            }
        }
        if (count($filtersTrimmed) < 1) {
            return $games;
        }
        dump($filtersTrimmed);
        $filteredGames = [];
        foreach($games as $game) {
            foreach($filtersTrimmed as $filter) {
                $filteredGame = $this->filterGame($game,$filter);
                if ($filteredGame) {
                    $filteredGames[] = $filteredGame;
                }
            }
        }
        return $filteredGames;

    }
    private function filterGame(ScheduleGame $game, $filter)
    {
        $homeTeam = $game->homeTeam;
        $awayTeam = $game->awayTeam;

        if (strpos($homeTeam->regTeamName,$filter) !== false) return $game;
        if (strpos($awayTeam->regTeamName,$filter) !== false) return $game;

        if (strpos($homeTeam->poolTeamView,$filter) !== false) return $game;
        if (strpos($awayTeam->poolTeamView,$filter) !== false) return $game;

        if (strpos($game->dow,      $filter) !== false) return $game;
        if (strpos($game->time,     $filter) !== false) return $game;
        if (strpos($game->fieldName,$filter) !== false) return $game;

        $officials = $game->getOfficials();
        foreach($officials as $official) {
            if (strpos($official->regPersonName,$filter) !== false) return $game;
        }
        return null;
    }
}