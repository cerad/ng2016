<?php
namespace AppBundle\Action\Schedule;

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

        if (stripos($homeTeam->regTeamName,$filter) !== false) return $game;
        if (stripos($awayTeam->regTeamName,$filter) !== false) return $game;

        if (stripos($homeTeam->poolTeamView,$filter) !== false) return $game;
        if (stripos($awayTeam->poolTeamView,$filter) !== false) return $game;

        if (stripos($game->dow,       $filter) !== false) return $game;
        if (stripos($game->time,      $filter) !== false) return $game;
        if (stripos($game->fieldName, $filter) !== false) return $game;
        if (stripos($game->gameNumber,$filter) !== false) return $game;

        $officials = $game->getOfficials();
        foreach($officials as $official) {
            if (stripos($official->regPersonName,$filter) !== false) return $game;
        }
        return null;
    }
}