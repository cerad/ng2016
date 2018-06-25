<?php
namespace AppBundle\Action\RegTeam\Init;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

class InitTeams2018Command extends Command
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
            ->setName('init:teams:noc2018')
            ->setDescription('Init Teams NOC2018');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Init Teams NOC2018 ...\n");

        $this->initTeams($this->allTeamsClubG16U);

        $commit = false;

        $this->initRegTeams($this->teamsClubG11U, false);

        $this->initPoolTeams($this->teamsClubG11U,false);

//        $this->assignRegTeamsToPoolPlayTeams($commit || false);

//        $this->initGames($commit || true);

        echo sprintf("Init Teams NOC2018 Completed.\n");
    }

    private $projectId = 'AYSONationalOpenCup2018';

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

    private $allTeamsClubG11U = [
        [
            'addRegTeam' => true,
            'regTeamKey' => 'G11UClub04', 'regTeamNumber' => 4, 'regTeamName' => '#04',
            'program' => 'Club', 'gender' => 'G', 'age' => '11U','division' => 'G11U',
            'addPoolTeam' => true,
            'poolTypeKey'  => 'PP','poolKey' => 'G11UClubPPA','poolTeamKey' => 'G11UClubPPA4',
            'poolTypeView' => 'PP','poolSlotView' => 'A', //7,
            'poolView'     => 'G11U Club PP A',
            'poolTeamView' => 'G11U Club PP A4',
            'poolTeamSlotView' => 'A4',
        ],
        [
            'add' => true,
            'program'  => 'Club', 'gender' => 'G', 'age' => '11U','division' => 'G11U',
            'addPoolTeam'  => true,
            'poolTypeKey'  => 'TF','poolKey' => 'G11UClubTF1','poolTeamKey' => 'G11UClubTF1X',
            'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
            'poolView'     => 'G11U Club Final<br>Championship',
            'poolTeamView' => 'G11U Club Final A 1st',
            'poolTeamSlotView' => 'A 1st',
        ],
        [
            'add' => true,
            'program'  => 'Club', 'gender' => 'G', 'age' => '11U','division' => 'G11U',
            'addPoolTeam'  => true,
            'poolTypeKey'  => 'TF','poolKey' => 'G11UClubTF1','poolTeamKey' => 'G11UClubTF1Y',
            'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
            'poolView'     => 'G11U Club Final<br>Championship',
            'poolTeamView' => 'G11U Club Final A 2nd',
            'poolTeamSlotView' => 'A 2nd',
        ],
    ];
    private $allTeamsClubG12U = [
        [
            'program'  => 'Club', 'gender' => 'G', 'age' => '12U','division' => 'G12U',
            'addPoolTeam'  => true,
            'poolTypeKey'  => 'TF','poolKey' => 'G12UClubTF1','poolTeamKey' => 'G12UClubTF1X',
            'poolTypeView' => 'FM','poolSlotView' => '', //7,
            'poolView'     => 'G12U Club Final<br>Championship',
            'poolTeamView' => 'G12U Club Final A 1st',
            'poolTeamSlotView' => 'A 1st',
        ],
        [
            'program'  => 'Club', 'gender' => 'G', 'age' => '12U','division' => 'G12U',
            'addPoolTeam'  => true,
            'poolTypeKey'  => 'TF','poolKey' => 'G12UClubTF1','poolTeamKey' => 'G12UClubTF1Y',
            'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
            'poolView'     => 'G12U Club Final<br>Championship',
            'poolTeamView' => 'G12U Club Final A 2nd',
            'poolTeamSlotView' => 'A 2nd',
        ],
    ];
    private $allTeamsClubG16U = [
        [
            'addRegTeam' => true,
            'regTeamKey' => 'G16UClub04', 'regTeamNumber' => 4, 'regTeamName' => '#04',
            'program' => 'Club', 'gender' => 'G', 'age' => '16U','division' => 'G16U',
            'addPoolTeam' => true,
            'poolTypeKey'  => 'PP','poolKey' => 'G16UClubPPA','poolTeamKey' => 'G16UClubPPA4',
            'poolTypeView' => 'PP','poolSlotView' => 'A',
            'poolView'     => 'G16U Club Pool A',
            'poolTeamView' => 'G16U Club Pool A4',
            'poolTeamSlotView' => 'A4',
        ],
        [
            'add' => true,
            'program'  => 'Club', 'gender' => 'G', 'age' => '16U','division' => 'G16U',
            'addPoolTeam'  => true,
            'poolTypeKey'  => 'TF','poolKey' => 'G16UClubTF1','poolTeamKey' => 'G16UClubTF1X',
            'poolTypeView' => 'FM', 'poolSlotView' => '',
            'poolView'     => 'G16U Club Final',
            'poolTeamView' => 'G16U Club Final A 1st',
            'poolTeamSlotView' => 'A 1st',
        ],
        [
            'add' => true,
            'program'  => 'Club', 'gender' => 'G', 'age' => '16U','division' => 'G16U',
            'addPoolTeam'  => true,
            'poolTypeKey'  => 'TF','poolKey' => 'G16UClubTF1','poolTeamKey' => 'G16UClubTF1Y',
            'poolTypeView' => 'FM', 'poolSlotView' => '',
            'poolView'     => 'G16U Club Final',
            'poolTeamView' => 'G16U Club Final A 2nd',
            'poolTeamSlotView' => 'A 2nd',
        ],
    ];
    private $teamsClubG11U = [
        'Club' => [
            'G11U' => [ // Club Girls 07
                'pools' => [
                    'A' => ['count' => 1, 'start' => 3],
                ],
                'medals' => [
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'G11UClubTF1','poolTeamKey' => 'G11UClubTF1X',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'G11U Club Final<br>Championship',
                        'poolTeamView' => 'G11U Club Final A 1st',
                        'poolTeamSlotView' => 'A 1st',
                    ],
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'G11UClubTF1','poolTeamKey' => 'G11UClubTF1Y',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'G11U Club Final<br>Championship',
                        'poolTeamView' => 'G11U Club Final A 2nd',
                        'poolTeamSlotView' => 'A 2nd',
                    ],
                ]
            ],
        ],
    ];

    private $teamsClub = [
        'Club' => [
            'G10U' => [ // Club Girls 07
                'pools' => [
                    'A' => ['count' => 4],
                ],
                'medals' => [
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'G10UClubTF1','poolTeamKey' => 'G10UClubTF1X',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'G10U Club Final<br>Championship',
                        'poolTeamView' => 'G10U Club Final A 1st',
                        'poolTeamSlotView' => 'A 1st',
                    ],
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'G10UClubTF1','poolTeamKey' => 'G10UClubTF1Y',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'G10U Club Final<br>Championship',
                        'poolTeamView' => 'G10U Club Final A 2nd',
                        'poolTeamSlotView' => 'A 2nd',
                    ],
                ]
            ],
            'G11U' => [ // Club Girls 06 No finals
                'pools' => [
                    'A' => ['count' => 3],
                ],
                'medals' => [],
            ],
            'G12U' => [ // Club Girls 05 No Finals
                'pools' => [
                    'A' => ['count' => 4],
                ],
                'medals' => [],
            ],
            'G14U' => [ // Club Girls 03-04 No Finals
                'pools' => [
                    'A' => ['count' => 5],
                ],
                'medals' => [],
            ],
            'G16U' => [ // Club Girls 01-02 No finals, 3 Games
                'pools' => [
                    'A' => ['count' => 3],
                ],
                'medals' => []
            ],
            'B11U' => [ // Club Boys 06-07
                'pools' => [
                    'A' => ['count' => 3],
                    'B' => ['count' => 6],
                ],
                'medals' => [
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'B11UClubTF1','poolTeamKey' => 'B11UClubTF1X',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'B11U Club Final<br>Championship',
                        'poolTeamView' => 'B11U Club Final A 1st',
                        'poolTeamSlotView' => 'A 1st',
                    ],
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'B11UClubTF1','poolTeamKey' => 'B11UClubTF1Y',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'B11U Club Final<br>Championship',
                        'poolTeamView' => 'B11U Club Final B 1st',
                        'poolTeamSlotView' => 'B 1st',
                    ],
                ],
            ],
            'B13U' => [ // Club Boys 04-05
                'pools' => [
                    'A' => ['count' => 4],
                ],
                'medals' => [
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'B13UClubTF1','poolTeamKey' => 'B13UClubTF1X',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'B13U Club Final<br>Championship',
                        'poolTeamView' => 'B13U Club Final A 1st',
                        'poolTeamSlotView' => 'A 1st',
                    ],
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'B13UClubTF1','poolTeamKey' => 'B13UClubTF1Y',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'B13U Club Final<br>Championship',
                        'poolTeamView' => 'B13U Club Final A 2nd',
                        'poolTeamSlotView' => 'A 2nd',
                    ],
                ],
            ],
        ],
    ];
    private $teamsExtra = [
        'Extra' => [
            'B12U' => [
                'pools' => [
                    'A' => ['count' => 4],
                    'B' => ['count' => 4],
                ],
                'medals' => [],
            ],
            'G14U' => [
                'pools' => [
                    'A' => ['count' => 6],
                ],
                'medals' => [
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'G14UExtraTF1','poolTeamKey' => 'G14UExtraTF1X',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'G14U Extra Final<br>Championship',
                        'poolTeamView' => 'G14U Extra Final A 1st',
                        'poolTeamSlotView' => 'A 1st',
                    ],
                    [
                        'poolTypeKey'  => 'TF','poolKey' => 'G14UExtraTF1','poolTeamKey' => 'G14UExtraTF1Y',
                        'poolTypeView' => 'FM', 'poolSlotView' => '', //7,
                        'poolView'     => 'G14U Extra Final<br>Championship',
                        'poolTeamView' => 'G14U Extra Final A 2nd',
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
    private $teamsCoreB14U = [
        'Core' => [
            'B14U' => [
                'pools' => [],
                'medals' => [
                    [
                        'poolTypeKey'  => 'PP','poolKey' => 'B14UCorePPA','poolTeamKey' => 'B14UCorePPA5',
                        'poolTypeView' => 'PP', 'poolSlotView' => 'A',
                        'poolView'     => 'B14U Pool Play A',
                        'poolTeamView' => 'B14U Pool Play A5',
                        'poolTeamSlotView' => 'A5',
                    ],
                    [
                        'poolTypeKey'  => 'PP','poolKey' => 'B14UCorePPA','poolTeamKey' => 'B14UCorePPA6',
                        'poolTypeView' => 'PP', 'poolSlotView' => 'A',
                        'poolView'     => 'B14U Pool Play A',
                        'poolTeamView' => 'B14U Pool Play A6',
                        'poolTeamSlotView' => 'A6',
                    ],
                    [
                        'poolTypeKey'  => 'PP','poolKey' => 'B14UCorePPA','poolTeamKey' => 'B14UCorePPA7',
                        'poolTypeView' => 'PP', 'poolSlotView' => 'A',
                        'poolView'     => 'B14U Pool Play A',
                        'poolTeamView' => 'B14U Pool Play A7',
                        'poolTeamSlotView' => 'A7',
                    ],
                    [
                        'poolTypeKey'  => 'PP','poolKey' => 'B14UCorePPA','poolTeamKey' => 'B14UCorePPA8',
                        'poolTypeView' => 'PP', 'poolSlotView' => 'A',
                        'poolView'     => 'B14U Pool Play A',
                        'poolTeamView' => 'B14U Pool Play A8',
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