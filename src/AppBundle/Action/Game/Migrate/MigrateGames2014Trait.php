<?php
namespace AppBundle\Action\Game\Migrate;

trait MigrateGames2014Trait
{
    /* ==============================================================
     * Parse level key
     */
    protected function parseLevelKey($levelKey)
    {
        $parts = explode('_',$levelKey);

        $division = $parts[1];
        $program  = $parts[2];
        $gender   = substr($division,3,1);
        $age      = substr($division,0,3);

        return [$program, $gender, $age, $division];
    }
    /* ==============================================================
     * Create all the pool information
     */
    protected function generatePoolInfo($program,$gender,$age,$division,$poolType,$poolName,$poolSlot)
    {
        $poolTypeView = $poolType;
        $poolTeamSlotView = $poolSlot;
        
        switch($poolType) {
            case 'FM':  $poolType = 'TF'; break;
            case 'SOF': $poolType = 'ZZ'; break;
        }
        $poolView     = sprintf('%s-%s %s %s %s',$age,$gender,$program,$poolType,$poolName);
        $poolTeamView = sprintf('%s-%s %s %s %s',$age,$gender,$program,$poolType,$poolSlot);

        $poolKey     = sprintf('%s-%s-%s-%s',$division,$program,$poolType,$poolName);
        $poolTeamKey = sprintf('%s-%s-%s-%s',$division,$program,$poolType,$poolSlot);
        
        if ($poolType == 'ZZ') {
            $poolTeamKey = sprintf('%s-%s-%s-%s-%s',$division,$program,$poolType,$poolName,$poolSlot);
        }

        return [$poolType,$poolKey,$poolTeamKey,$poolTypeView,$poolView,$poolTeamView,$poolTeamSlotView];
    }
    protected function generateProjectTeamId($projectKey,$teamKey,$program,$division)
    {
        if (!$teamKey) {
            return null;   
        }
        $parts = explode(':', $teamKey);
        $teamNumber = (integer)$parts[2];
        return sprintf('%s:%s-%s-%02d',$projectKey,$division,$program,$teamNumber);
    }
}