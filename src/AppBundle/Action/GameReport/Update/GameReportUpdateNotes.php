<?php
// TODO Make this a real template, not an include thing
$notes = <<<EOT
    <legend class="text-left">Scoring Notes</legend>

    <div class="app_table" id="notes">
    <table>
      <tbody>
        <tr>
          <td width="10%"></td>
          <td style="vertical-align: top;" width="35%">
            <ul>
              <li>Enter score and other info then click "Save"</li>
              <li>Status fields will update themselves</li>
              <br><br>
              <li><strong>NOTE:</strong> Six points for proper participation in Soccerfest are added separately</li>
            </ul>
          </td>
          <td width="35%">
            <ul>
              <ul>Points earned will be calculated</ul>
              <li>Win: 6 pts / Tie: 3 pts / Shutout: 1 pt</li>
              <li>For winner only: 1 pt per goal (3 pts max)
              <li>Player Cautions: No impact</li>
              <li>Player Sendoffs: -1 pt per sendoff</li>
              <li>Coach/Substitute Ejections: -1 pt per ejection</li>
              <li>FORFEIT: Score as 1-0</li>
            </ul>
          </td>
          <td width="10%"></td>
        </tr>
        <tr></tr>
        <tr>
          <td width="10%"></td>
          <td style="vertical-align: top;" width="35%" colspan=2>
            <ul class="cerad-common-help">
            <ul class="ul_bullets">
                <li>For help with Match Reporting, contact {$this->project['support']['name']} at <a href="mailto:{$this->project['support']['email']}">{$this->project['support']['email']}</a> or at {$this->project['support']['phone']}</li>
                <li>For help with Schedule Management, contact {$this->project['schedules']['name']} at <a href="mailto:{$this->project['schedules']['email']}">{$this->project['schedules']['email']}</a> or at {$this->project['schedules']['phone']}</li>
                <li>For help with Account Management, contact {$this->project['support']['name']} at <a href="mailto:{$this->project['support']['email']}">{$this->project['support']['email']}</a> or at {$this->project['support']['phone']}</li>
            </ul></ul>
          </td>
        </tr>
      </tbody>
    </table>
    </div>
EOT;
?>