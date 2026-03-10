<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<TaskQueue>
 */
class TaskQueueRepository extends CommonRepository
{
    public function getTableAlias(): string
    {
        return 'tq';
    }

    /**
     * @return array<array<string>>
     */
    protected function getDefaultOrder(): array
    {
        return [
            [$this->getTableAlias().'.name', 'ASC'],
        ];
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     *
     * @return array{string, array<mixed>}
     */
    protected function addCatchAllWhereClause($q, $filter): array
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, ['tq.name']);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     *
     * @return array{string, array<mixed>}
     */
    protected function addSearchCommandWhereClause($q, $filter): array
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        return $this->getStandardSearchCommands();
    }

    /**
     * @return TaskQueue[]
     */
    public function findAccessibleQueues(int $userId): array
    {
        return $this->createQueryBuilder('tq')
            ->where('tq.owner = :userId')
            ->orWhere('tq.isShared = :shared')
            ->setParameter('userId', $userId)
            ->setParameter('shared', true)
            ->orderBy('tq.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
