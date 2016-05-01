<?php
namespace AppBundle\Common;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

trait QueryBuilderTrait
{
    protected function addWhere(QueryBuilder $qb, array $metas, array $criteria)
    {
        $values = [];
        $types  = [];

        foreach($metas as $key => $col) {
            if (isset($criteria[$key]) && count($criteria[$key])) {
                $qb->andWhere($col . ' IN (?)');
                $values[] = $criteria[$key];
                $types[]  = Connection::PARAM_STR_ARRAY;
            }
        }
        return [$values,$types];
    }

}