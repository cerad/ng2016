<?php

namespace AppBundle\Action\Schedule\Team;

use AppBundle\Action\AbstractController;

use Cerad\Bundle\ProjectBundle\EntityRepository\ProjectTeamRepository;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TeamController extends AbstractController
{
    /** @var  ProjectTeamRepository */
    private $projectTeamRepository;

    private $projectTeams;
    private $projectTeamKeys = [];

    private $projectGames = [];

    public function __construct(ProjectTeamRepository $projectTeamRepository)
    {
        $this->projectTeamRepository = $projectTeamRepository;
    }
    public function __invoke(Request $request)
    {
        // Save selected teams in session
        $session = $request->getSession();
        $this->projectTeamKeys = $session->has('project_team_keys') ? $session->get('project_team_keys') : [];

        // Search posted
        if ($request->isMethod('POST')) {
            $this->projectTeamKeys = $request->request->get('project_teams');
            $session->set('project_team_keys',$this->projectTeamKeys);
        }

        // Find games
        $this->projectGames = $this->projectTeamRepository->findProjectGames($this->projectTeamKeys);

        // Search form
        $this->projectTeams = $this->projectTeamRepository->findAll();

        return new Response($this->renderPage());
    }
    private function renderProjectTeamOptions()
    {
        $html = null;
        foreach($this->projectTeams as $projectTeam) {

            $selected = in_array($projectTeam->key,$this->projectTeamKeys) ? ' selected' : null;

            $levelParts = explode('_',$projectTeam->levelKey);
            $div = $levelParts[1];
            $html .= sprintf('<option%s value="%s">%s %s</option>' . "\n",
                $selected,
                $this->escape($projectTeam->key),
                $this->escape($div),
                $this->escape($projectTeam->name));
        }
        return $html;
    }
    protected function renderPage()
    {
        $projectTeamCount = count($this->projectTeams);

        $content = <<<EOD
<div>
  <h3>Teams({$projectTeamCount})</span></h3>
  <form method="post" action="{$this->generateUrl('app_schedule_team')}">
    <select name="project_teams[]" multiple size="10">
      <option value=0">Select Teams</option>
      {$this->renderProjectTeamOptions()}
    </select><br />
    <button type="submit">Show</button>
  </form>
</div>
EOD;
        $baseTemplate = $this->getBaseTemplate();
        $baseTemplate->setContent($content);
        return $baseTemplate->render();
    }
}
