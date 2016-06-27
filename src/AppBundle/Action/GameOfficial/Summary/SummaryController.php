<?php
namespace AppBundle\Action\GameOfficial\Summary;

use AppBundle\Action\AbstractController2;

use AppBundle\Action\Game\GameFinder;

use AppBundle\Action\RegPerson\RegPersonFinder;
use Symfony\Component\HttpFoundation\Request;

class SummaryController extends AbstractController2
{
    private $gameFinder;
    private $regPersonFinder;

    public function __construct(
        GameFinder      $gameFinder,
        RegPersonFinder $regPersonFinder
    ) {
        $this->gameFinder =      $gameFinder;
        $this->regPersonFinder = $regPersonFinder;
    }
    public function __invoke(Request $request, $projectId, $program)
    {
        
        // Grab entire games database
        $criteria = [
            'projectIds'    => [$projectId],
            'programs'      => [$program],
            'wantOfficials' => true]
        ;
        $games = $this->gameFinder->findGames($criteria);
        $request->attributes->set('games',$games);

        // Grab the reg person database and filter for referees
        $regPersons = $this->regPersonFinder->findRegPersons($projectId);
        $request->attributes->set('regPersons',$regPersons);

        //echo sprintf("Game Count: %d, Reg Person Count: %d.\n",count($games),count($regPersons));
        //die();
        return null;
    }
}
