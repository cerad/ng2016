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

        $this->initTeams($this->allTeamsClubU12G);

        //$commit = false;

        //$this->initRegTeams($this->teamsClubU11G, false);

        //$this->initPoolTeams($this->teamsClubU11G,false);

        //$this->assignRegTeamsToPoolPlayTeams($commit || false);

        //$this->initGames($commit || true);

        echo sprintf("Init Teams AOC2017 Completed.\n");
    }

    private $projectId = 'AYSONationalOpenCup2017';

    private function initTeams($teams)
    {
        $projectId = $this->projectId;

        $regTeamCount = 0;
        $poolTeamCount = 0;

        // Cycle through each program
        foreach($teams as $team)
        {
            if (isset($team['regTeamKey'])) {
                $regTeam = [
                    'regTeamId'  => $projectId . ':' . $team['regTeamKey'],
                    'projectId'  => $projectId,
                    'teamKey'    => $team['regTeamKey'],
                    'teamNumber' => $team['regTeamNumber'],
                    'teamName'   => $team['regTeamName'],

                    'program'  => $team['program'],
                    'gender'   => $team['gender'],
                    'age'      => $team['age'],
                    'division' => $team['division'],
                ];
                if ($team['addRegTeam']) {
                    $this->regTeamConn->insert('regTeams', $regTeam);
                    $regTeamCount++;
                }
            }
            $poolTeam = [
                'poolTeamId' => $projectId . ':' . $team['poolTeamKey'],
                'projectId'  => $projectId,

                'poolKey'  => $team['poolKey'],
                'poolView' => $team['poolView'],

                'poolTypeKey'  => $team['poolTypeKey'],
                'poolTypeView' => $team['poolTypeView'],

                'poolTeamKey'  => $team['poolTeamKey'],
                'poolTeamView' => $team['poolTeamView'],

                'poolSlotView'     => $team['poolSlotView'],
                'poolTeamSlotView' => $team['poolTeamSlotView'],

                'program'  => $team['program'],
                'gender'   => $team['gender'],
                'age'      => $team['age'],
                'division' => $team['division'],

            ];
            if ($team['addPoolTeam']) {
                $this->gameConn->insert('poolTeams', $poolTeam);
                $poolTeamCount++;
            }
        }
        echo sprintf("Team Count: %d %d\n",$regTeamCount,$poolTeamCount);
    }

    private $allTeamsClubU11G = [
        [
            'addRegTeam' => true,
            'regTeamKey' => 'U11GClub04', 'regTeamNumber' => 4, 'regTeamName' => '#04',
            'program' => 'Club', 'gender' => 'G', 'age' => 'U11','division' => 'U11G',
            'addPoolTeam' => true,
            'poolTypeKey'  => 'PP','poolKey' => 'U11GClubPPA','poolTeamKey' => 'U11GClubPPA4',
            'poolTypeView' => 'PP','poolSlotView' => 'A', //7,
            'poolView'     => 'U11-G Club PP A',
            'poolTeamView' => 'U11-G Club PP A4',
            'poolTeamSlotView' => 'A4',
        ],
        [
            'add' => true,
            'program'  => 'Club', 'gender' => 'G', 'age' => 'U11','division' => 'U11G',
            'addPoolTeam'  => true,
            'poolTypeKey'  => 'TF','poolKey' => 'U11GClubTF1','poolTeamKey' => 'U11GClubTF1X',
            'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
            'poolView'     => 'U11-G Club Final<br>Championship',
            'poolTeamView' => 'U11-G Club Final A 1st',
            'poolTeamSlotView' => 'A 1st',
        ],
        [
            'add' => true,
            'program'  => 'Club', 'gender' => 'G', 'age' => 'U11','division' => 'U11G',
            'addPoolTeam'  => true,
            'poolTypeKey'  => 'TF','poolKey' => 'U11GClubTF1','poolTeamKey' => 'U11GClubTF1Y',
            'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
            'poolView'     => 'U11-G Club Final<br>Championship',
            'poolTeamView' => 'U11-G Club Final A 2nd',
            'poolTeamSlotView' => 'A 2nd',
        ],
    ];
    private $allTeamsClubU12G = [
        [
            'program'  => 'Club', 'gender' => 'G', 'age' => 'U12','division' => 'U12G',
            'addPoolTeam'  => true,
            'poolTypeKey'  => 'TF','poolKey' => 'U12GClubTF1','poolTeamKey' => 'U12GClubTF1X',
            'poolTypeView' => 'FM','poolSlotView' => '', //7,
            'poolView'     => 'U12-G Club Final<br>Championship',
            'poolTeamView' => 'U12-G Club Final A 1st',
            'poolTeamSlotView' => 'A 1st',
        ],
        [
            'program'  => 'Club', 'gender' => 'G', 'age' => 'U12','division' => 'U12G',
            'addPoolTeam'  => true,
            'poolTypeKey'  => 'TF','poolKey' => 'U12GClubTF1','poolTeamKey' => 'U12GClubTF1Y',
            'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
            'poolView'     => 'U12-G Club Final<br>Championship',
            'poolTeamView' => 'U12-G Club Final A 2nd',
            'poolTeamSlotView' => 'A 2nd',
        ],
    ];
    private $teamsClubU11G = [
        'Club' => [
            'U11G' => [ // Club Girls 07
                'pools' => [
                    'A' => ['count' => 1, 'start' => 3],
                ],
                'medals' => [
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'U11GClubTF1','poolTeamKey' => 'U11GClubTF1X',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'U11-G Club Final<br>Championship',
                        'poolTeamView' => 'U11-G Club Final A 1st',
                        'poolTeamSlotView' => 'A 1st',
                    ],
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'U11GClubTF1','poolTeamKey' => 'U11GClubTF1Y',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'U11-G Club Final<br>Championship',
                        'poolTeamView' => 'U11-G Club Final A 2nd',
                        'poolTeamSlotView' => 'A 2nd',
                    ],
                ]
            ],
        ],
    ];

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
            'AdultCoed' => [
                'pools' => ['A' => ['count' => 4]],
                'medals' => [
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'AdultCoedAdultTF1','poolTeamKey' => 'AdultCoedAdultTF1X',
                        'poolTypeView' => 'FM','poolSlotView' => '', //7,
                        'poolView'     => 'Adult Coed Final<br>Championship',
                        'poolTeamView' => 'Adult Coed Final A 1st',
                        'poolTeamSlotView' => 'A 1st',
                    ],
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'AdultCoedAdultTF1','poolTeamKey' => 'AdultCoedAdultTF1Y',
                        'poolTypeView' => 'FM','poolSlotView' => '', //7,
                        'poolView'     => 'Adult Coed Final<br>Championship',
                        'poolTeamView' => 'Adult Coed Final A 2nd',
                        'poolTeamSlotView' => 'A 2nd',
                    ],
                ]
            ],
            'AdultMen' => [
                'pools' => ['A' => ['count' => 4]],
                'medals' => [
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'AdultMenAdultTF1','poolTeamKey' => 'AdultMenAdultTF1X',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'Adult Men Final<br>Championship',
                        'poolTeamView' => 'Adult Men Final A 1st',
                        'poolTeamSlotView' => 'A 1st',
                    ],
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'AdultMenAdultTF1','poolTeamKey' => 'AdultMenAdultTF1Y',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'Adult Men Final<br>Championship',
                        'poolTeamView' => 'Adult Men Final A 2nd',
                        'poolTeamSlotView' => 'A 2nd',
                    ],
                ]
            ],
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

    private function initRegTeams($teams, $clear = false)
    {
        $projectId = $this->projectId;

        // Clear any existing teams
        if ($clear) {
            foreach (array_keys($teams) as $program) {
                $this->regTeamConn->delete('regTeams', ['projectId' => $projectId, 'program' => $program]);
            }
        }
        $teamCount = 0;
        // Cycle through each program
        foreach($teams as $program => $divisions)
        {
            foreach($divisions as $division => $info) {
                $age = substr($division,0,3);
                $gender = substr($division,3,1);
                if ($division === 'AdultCoed') {
                    $age = 'Adult';
                    $gender = 'C';
                }
                if ($division === 'AdultMen') {
                    $age = 'Adult';
                    $gender = 'B';
                }

                foreach($info['pools'] as $pool) {
                    $teamNumber = isset($info['start']) ? $info['start'] : 0;
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
    private function initPoolTeams($teams,$clear = false)
    {
        $projectId = $this->projectId;

        // Clear any existing teams
        if ($clear) {
            foreach (array_keys($teams) as $program) {
                $this->gameConn->delete('poolTeams', ['projectId' => $projectId, 'program' => $program]);
            }
        }
        $teamCount = 0;

        // Cycle through each program
        foreach($teams as $program => $divisions)
        {
            foreach($divisions as $division => $info) {
                $age = substr($division,0,3);
                $gender = substr($division,3,1);
                if ($division === 'AdultCoed') {
                    $age = 'Adult';
                    $gender = 'C';
                }
                if ($division === 'AdultMen') {
                    $age = 'Adult';
                    $gender = 'B';
                }
                foreach($info['pools'] as $poolName =>  $pool) {
                    $poolTypeKey  = 'PP';
                    $poolTypeView = 'PP';
                    $poolKey = $division . $program. $poolTypeKey . $poolName;

                    $teamNumber = isset($info['start']) ? $info['start'] : 0;

                    for ($count = 1; $count <= $pool['count']; $count++) {

                        $teamNumber++;

                        $poolTeamKey = $poolKey . $teamNumber;
                        $poolTeamId = $projectId.':'.$poolTeamKey;

                        $poolView = sprintf('%s-%s %s PP %s',$age,$gender,$program,$poolName);
                        if ($division === 'AdultMen') {
                            $poolView = sprintf('Adult Men PP %s',$poolName);
                        }
                        if ($division === 'AdultCoed') {
                            $poolView = sprintf('Adult Coed PP %s',$poolName);
                        }
                        $poolTeamView = $poolView . $teamNumber;

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