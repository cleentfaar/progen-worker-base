<?php
namespace Cleentfaar\ProGen\Worker\Base\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class TaskRepository extends EntityRepository
{
	public function findAllForThisWorker(array $taskTypes)
	{
        $qb = $this->createQueryBuilder('t');

        $result = $qb
            ->where($qb->expr()->in('t.type', '?1'))
            ->andWhere('t.queued = 1')
            ->andWhere('t.running = 0')
            ->setParameter(1, $taskTypes)
            ->getQuery()
            ->getResult();
        return $result;
	}
}