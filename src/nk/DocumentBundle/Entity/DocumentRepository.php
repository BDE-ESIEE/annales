<?php

namespace nk\DocumentBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

/**
 * DocumentRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DocumentRepository extends EntityRepository
{
    public function findDistinct($field)
    {
        $result = $this->createQueryBuilder('d')
            ->select("DISTINCT d.$field AS val")
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        return array_map(function($line){ return $line['val']; }, $result);
    }

    public function search(array $mappedQuery)
    {
        $qb = $this->createQueryBuilder('d');

        foreach($mappedQuery as $field => $data){
            if($field == 'subject'){
                foreach($data as $id => $keyword)
                    $qb->andWhere("d.$field LIKE :keyword$id")
                        ->setParameter("keyword$id", "%$keyword%");

            }else{
                $qb->andWhere("d.$field IN (:$field)")
                    ->setParameter($field, $data);
            }
        }

        return $qb->getQuery()->getResult();
    }
}
