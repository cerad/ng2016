<?php declare(strict_types=1);

namespace App\Ayso;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AysoCommand extends Command
{
    protected static $defaultName = 'ayso:test';

    private $finder;

    public function __construct(AysoVolFinder $finder)
    {
        parent::__construct();

        $this->finder = $finder;
    }

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $aysoidArt   = 'AYSOV:99437977';
        $aysoidEthan = 'AYSOV:51563588';

        echo "AYSO Test\n";
        $vol = $this->finder->find($aysoidArt);
        dump($vol);
    }
}