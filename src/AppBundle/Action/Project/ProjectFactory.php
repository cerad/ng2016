<?php
namespace AppBundle\Action\Project;

class ProjectFactory
{
    public function createProjectPerson()
    {
        return [
            'projectKey' => null,
            'personKey'  => null,
            'orgKey'     => null, // sar etc

            'name'   => null, // Unique
            'email'  => null, // Unique?
            'phone'  => null,
            'badge'  => null, // Referee Badge
            'gender' => null,
            'age'    => null,

            'notes'  => null,
            'status' => 'Active', // Verified?
            
            'plans' => [
                'willAttend'  => 'no', // ROLE_ATTENDEE
                'willReferee' => 'no', // ROLE_REFEREE
            ],
            'roles' => [],
        ];
    }
    public function createProjectGameReport($game = [])
    {
        return [
            'type'   => 'project_game_report',
            'desc'   => null,
            'status' => 'Pending',
            'notes'  => null,
            'game'   => $game,
            'teamReports' => [],
        ];
    }
    public function createProjectGameTeamReport($team = [])
    {
        return [
            'type' => 'project_game_team_report',
            'team' => $team,
            
            'status' => 'Pending', // Empty, Entered, Verified

            'goalsScored'  => null, // Null serves as flag that the game was played
            'goalsAllowed' => null,

            'pointsEarned' => 0,
            'pointsMinus'  => 0,

            'playerWarnings'  => 0,
            'playerEjections' => 0,
            'coachWarnings'   => 0,
            'coachEjections'  => 0,
            'benchWarnings'   => 0,
            'benchEjections'  => 0,
            'specWarnings'    => 0,
            'specEjections'   => 0,
            'totalWarnings'   => 0, // TODO
            'totalEjections'  => 0,

            'sportsmanship'   => 0,
            'injuries'        => 0,
            'fudgeFactor'     => 0,
        ];
    }
    public function createProjectPoolTeamReport($team)
    {
        $report = $this->createProjectGameTeamReport();
        return array_merge($report,[

            'type' => 'project_pool_team_report',

            'team' => $team,

            'goalsScoredMax'   => null,
            'goalsAllowedMax'  => null,
            'goalDifferential' => null,

            'gamesTotal'  => 0,
            'gamesPlayed' => 0,
            'gamesWon'    => 0,
            'gamesLost'   => 0,
            'gamesTied'   => 0,

            'winPercent'  => null,
        ]);
    }
}