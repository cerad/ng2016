<?php declare(strict_types=1);

namespace Zayso\Fed\Ayso;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AysoFindCommand extends Command
{
    const aysoidArt   = 'AYSO:99437977';
    const aysoidEthan = 'AYSO:51563588';

    const projectIdNG2019 = 'AYSONationalGames2019';

    protected static $defaultName = 'fed:ayso:find';

    private $finder;

    public function __construct(AysoFinder $finder)
    {
        parent::__construct();

        $this->finder = $finder;
    }
    protected function configure()
    {
        $this
            ->addArgument('fedPersonKey', InputArgument::OPTIONAL, '8 digit ayso id')
        ;
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo "AYSO Find\n";

        $fedPersonKey = $input->getArgument('fedPersonKey') ? $input->getArgument('fedPersonKey') : self::aysoidArt;

        $fedPerson = $this->finder->find($fedPersonKey);

        dump($fedPerson);
    }
}