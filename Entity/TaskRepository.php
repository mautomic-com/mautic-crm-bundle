<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Task>
 */
class TaskRepository extends CommonRepository
{
    public function getTableAlias(): string
    {
        return 't';
    }

    /**
     * @return array<array<string>>
     */
    protected function getDefaultOrder(): array
    {
        return [
            [$this->getTableAlias().'.dueDate', 'ASC'],
        ];
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     *
     * @return array{string, array<mixed>}
     */
    protected function addCatchAllWhereClause($q, $filter): array
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, ['t.title']);
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
     * @return Task[]
     */
    public function findByDeal(int $dealId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.deal = :dealId')
            ->setParameter('dealId', $dealId)
            ->orderBy('t.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
