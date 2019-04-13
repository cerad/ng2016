<?php declare(strict_types=1);

namespace Zayso\Reg\Person;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RegPersonFindCommand extends Command
{
    const AysoIdArt   = 'AYSO:99437977';
    const AysoIdRick  = 'AYSO:97815888';
    const AysoIdEthan = 'AYSO:51563588';

    const PersonIdArt   = 'C4AF1DBD-4945-4269-97A6-E2E203319D58';
    const PersonIdRick  = '7A43DF09-7D0F-4CA2-B991-305094B2340E';
    const PersonIdEthan = '016933EE-B24D-4EA6-BD84-D678BFA96C45';

    const ProjectIdNG2019 = 'AYSONationalGames2019';
    const ProjectIdNG2016 = 'AYSONationalGames2016';
    const ProjectIdNG2014 = 'AYSONationalGames2014';

    protected static $defaultName = 'reg:person:find';

    private $regPersonFinder;

    public function __construct(RegPersonFinder $regPersonFinder)
    {
        parent::__construct();

        $this->regPersonFinder = $regPersonFinder;
    }

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $regPerson = $this->regPersonFinder->findByProjectPerson(self::ProjectIdNG2016,self::PersonIdArt);
        dump($regPerson);
    }
}