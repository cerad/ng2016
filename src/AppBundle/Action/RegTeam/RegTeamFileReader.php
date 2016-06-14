<?php

namespace AppBundle\Action\RegTeam;

use AppBundle\Action\AbstractImporter;

class RegTeamFileReader extends AbstractImporter
{
    public function fileToArray($file)
    {
        $dataArray = $this->import($file);
        
        return $dataArray;
    }
    
}
