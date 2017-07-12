<?php
namespace AppBundle\Action\RegTeam\Init;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

class InitTeams2017Command extends Command
{
    private $gameConn;
    private $regTeamConn;

    public function __construct(Connection $conn)
    {
        parent::__construct();

        $this->gameConn    = $conn;
        $this->regTeamConn = $conn;
    }
    protected function configure()
    {
        $this
            ->setName('init:teams:aoc2017')
            ->setDescription('Init Teams AOC2016');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Init Teams AOC2016 ...\n");

        //$commit = false;

        $this->initRegTeams($this->teamsClub);

        $this->initPoolTeams($this->teamsClub);

        //$this->assignRegTeamsToPoolPlayTeams($commit || false);

        //$this->initGames($commit || true);

        echo sprintf("Init Teams AOC2017 Completed.\n");
    }

    private $projectId = 'AYSONationalOpenCup2017';

    private $teamsClub = [
        'Club' => [
            'U10G' => [ // Club Girls 07
                'pools' => [
                    'A' => ['count' => 4],
                ],
                'medals' => [
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'U10GClubTF1','poolTeamKey' => 'U10GClubTF1X',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'U10-G Club Final<br>Championship',
                        'poolTeamView' => 'U10-G Club Final A 1st',
                        'poolTeamSlotView' => 'A 1st',
                    ],
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'U10GClubTF1','poolTeamKey' => 'U10GClubTF1Y',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'U10-G Club Final<br>Championship',
                        'poolTeamView' => 'U10-G Club Final A 2nd',
                        'poolTeamSlotView' => 'A 2nd',
                    ],
                ]
            ],
            'U11G' => [ // Club Girls 06 No finals
                'pools' => [
                    'A' => ['count' => 3],
                ],
                'medals' => [],
            ],
            'U12G' => [ // Club Girls 05 No Finals
                'pools' => [
                    'A' => ['count' => 4],
                ],
                'medals' => [],
            ],
            'U14G' => [ // Club Girls 03-04 No Finals
                'pools' => [
                    'A' => ['count' => 5],
                ],
                'medals' => [],
            ],
            'U16G' => [ // Club Girls 01-02 No finals, 3 Games
                'pools' => [
                    'A' => ['count' => 3],
                ],
                'medals' => []
            ],
            'U11B' => [ // Club Boys 06-07
                'pools' => [
                    'A' => ['count' => 3],
                    'B' => ['count' => 6],
                ],
                'medals' => [
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'U11BClubTF1','poolTeamKey' => 'U11BClubTF1X',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'U11-B Club Final<br>Championship',
                        'poolTeamView' => 'U11-B Club Final A 1st',
                        'poolTeamSlotView' => 'A 1st',
                    ],
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'U11BClubTF1','poolTeamKey' => 'U11BClubTF1Y',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'U11-B Club Final<br>Championship',
                        'poolTeamView' => 'U11-B Club Final B 1st',
                        'poolTeamSlotView' => 'B 1st',
                    ],
                ],
            ],
            'U13B' => [ // Club Boys 04-05
                'pools' => [
                    'A' => ['count' => 4],
                ],
                'medals' => [
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'U13BClubTF1','poolTeamKey' => 'U13BClubTF1X',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'U13-B Club Final<br>Championship',
                        'poolTeamView' => 'U13-B Club Final A 1st',
                        'poolTeamSlotView' => 'A 1st',
                    ],
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'U13BClubTF1','poolTeamKey' => 'U13BClubTF1Y',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'U13-B Club Final<br>Championship',
                        'poolTeamView' => 'U13-B Club Final A 2nd',
                        'poolTeamSlotView' => 'A 2nd',
                    ],
                ],
            ],
        ],
    ];
    private $teamsExtra = [
        'Extra' => [
            'U12B' => [
                'pools' => [
                    'A' => ['count' => 4],
                    'B' => ['count' => 4],
                ],
                'medals' => [],
            ],
            'U14G' => [
                'pools' => [
                    'A' => ['count' => 6],
                ],
                'medals' => [
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'U14GExtraTF1','poolTeamKey' => 'U14GExtraTF1X',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'U14-G Extra Final<br>Championship',
                        'poolTeamView' => 'U14-G Extra Final A 1st',
                        'poolTeamSlotView' => 'A 1st',
                    ],
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'U14GExtraTF1','poolTeamKey' => 'U14GExtraTF1Y',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'U14-G Extra Final<br>Championship',
                        'poolTeamView' => 'U14-G Extra Final A 2nd',
                        'poolTeamSlotView' => 'A 2nd',
                    ],
                ]
            ],
        ],
    ];
    private $teamsAdult = [
        'Adult' => [
            'Adult' => [
                'pools' => [
                    'A' => ['count' => 4],
                    'B' => ['count' => 4],
                ],
                'medals' => [],
            ]
        ]
    ];
    private $teamsCoreU14B = [
        'Core' => [
            'U14B' => [
                'pools' => [],
                'medals' => [
                    [
                        'poolTypeKey'  => 'PP','poolKey' => 'U14BCorePPA','poolTeamKey' => 'U14BCorePPA5',
                        'poolTypeView' => 'PP', 'poolSlotView' => 'A',
                        'poolView'     => 'U14-B Pool Play A',
                        'poolTeamView' => 'U14-B Pool Play A5',
                        'poolTeamSlotView' => 'A5',
                    ],
                    [
                        'poolTypeKey'  => 'PP','poolKey' => 'U14BCorePPA','poolTeamKey' => 'U14BCorePPA6',
                        'poolTypeView' => 'PP', 'poolSlotView' => 'A',
                        'poolView'     => 'U14-B Pool Play A',
                        'poolTeamView' => 'U14-B Pool Play A6',
                        'poolTeamSlotView' => 'A6',
                    ],
                    [
                        'poolTypeKey'  => 'PP','poolKey' => 'U14BCorePPA','poolTeamKey' => 'U14BCorePPA7',
                        'poolTypeView' => 'PP', 'poolSlotView' => 'A',
                        'poolView'     => 'U14-B Pool Play A',
                        'poolTeamView' => 'U14-B Pool Play A7',
                        'poolTeamSlotView' => 'A7',
                    ],
                    [
                        'poolTypeKey'  => 'PP','poolKey' => 'U14BCorePPA','poolTeamKey' => 'U14BCorePPA8',
                        'poolTypeView' => 'PP', 'poolSlotView' => 'A',
                        'poolView'     => 'U14-B Pool Play A',
                        'poolTeamView' => 'U14-B Pool Play A8',
                        'poolTeamSlotView' => 'A8',
                    ],
                ],
            ]
        ]
    ];

    private function initRegTeams($teams)
    {
        $projectId = $this->projectId;

        // Clear any existing teams
        foreach(array_keys($teams) as $program) {
            $this->regTeamConn->delete('regTeams', ['projectId' => $projectId, 'program' => $program]);
        }
        $teamCount = 0;
        // Cycle through each program
        foreach($teams as $program => $divisions)
        {
            foreach($divisions as $division => $info) {
                $age = substr($division,0,3);
                $gender = substr($division,3,1);
                if ($division === 'Adult') {
                    $age = 'Adult';
                    $gender = 'B';
                }
                $teamNumber = 0;
                foreach($info['pools'] as $pool) {
                    $count = $pool['count'];
                    for (; $count; $count--) {
                        $teamNumber++;
                        $teamKey = sprintf('%s%s%02d', $division, $program, $teamNumber);
                        $teamId = $projectId.':'.$teamKey;
                        $team = [
                            'regTeamId' => $teamId,
                            'projectId' => $projectId,
                            'teamKey' => $teamKey,
                            'teamNumber' => $teamNumber,
                            'teamName' => sprintf('#%02d', $teamNumber),
                            'teamPoints' => null,

                            'orgId'   => null,
                            'orgView' => null,

                            'program'  => $program,
                            'gender'   => $gender,
                            'age'      => $age,
                            'division' => $division,
                        ];
                        $this->regTeamConn->insert('regTeams', $team);
                        $teamCount++;
                    }
                }
            }
        }
        echo sprintf("Reg  Team Count: %d\n",$teamCount);
    }
    private function initPoolTeams($teams)
    {
        $projectId = $this->projectId;

        // Clear any existing teams
        foreach(array_keys($teams) as $program) {
            $this->gameConn->delete('poolTeams', ['projectId' => $projectId, 'program' => $program]);
        }
        $teamCount = 0;

        // Cycle through each program
        foreach($teams as $program => $divisions)
        {
            foreach($divisions as $division => $info) {
                $age = substr($division,0,3);
                $gender = substr($division,3,1);
                if ($division === 'Adult') {
                    $age = 'Adult';
                    $gender = 'B';
                }
                foreach($info['pools'] as $poolName =>  $pool) {
                    $poolTypeKey  = 'PP';
                    $poolTypeView = 'PP';
                    $poolKey = $division . $program. $poolTypeKey . $poolName;

                    for ($count = 1; $count <= $pool['count']; $count++) {

                        $poolTeamKey = $poolKey . $count;
                        $poolTeamId = $projectId.':'.$poolTeamKey;

                        $poolView = sprintf('%s-%s %s PP %s',$age,$gender,$program,$poolName);
                        if ($program === 'Adult') {
                            $poolView = sprintf('Adult Pool Play %s',$poolName);
                        }
                        $poolTeamView = $poolView . $count;

                        $poolTeam = [
                            'poolTeamId' => $poolTeamId,
                            'projectId' => $projectId,

                            'poolKey'  => $poolKey,
                            'poolView' => $poolView,

                            'poolTypeKey'  => $poolTypeKey,
                            'poolTypeView' => $poolTypeView,

                            'poolTeamKey'  => $poolTeamKey,
                            'poolTeamView' => $poolTeamView,

                            'poolSlotView' => $poolName,
                            'poolTeamSlotView' => $poolName . $count,

                            'program' => $program,
                            'gender' => $gender,
                            'age' => $age,
                            'division' => $division,
                        ];
                        $this->gameConn->insert('poolTeams', $poolTeam);
                        $teamCount++;
                    }
                }
                foreach($info['medals'] as $team) {
                    $team['poolTeamId'] = $projectId . ':' . $team['poolTeamKey'];
                    $team['projectId']  = $projectId;
                    $team['program']    = $program;
                    $team['gender']     = $gender;
                    $team['age']        = $age;
                    $team['division']   = $division;
                    $this->gameConn->insert('poolTeams', $team);
                    $teamCount++;
                }
            }
        }
        echo sprintf("Pool Team Count: %d\n",$teamCount);
    }
}