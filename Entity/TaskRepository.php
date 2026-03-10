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

    /**
     * @return Task[]
     */
    public function findTasksNeedingReminder(\DateTimeInterface $now): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.reminderDate IS NOT NULL')
            ->andWhere('t.reminderDate <= :now')
            ->andWhere('t.reminderSent = :notSent')
            ->andWhere('t.status = :open')
            ->setParameter('now', $now)
            ->setParameter('notSent', false)
            ->setParameter('open', 'open')
            ->orderBy('t.reminderDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Task[]
     */
    public function findOverdueTasks(?int $ownerId = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.dueDate < :now')
            ->andWhere('t.status = :open')
            ->setParameter('now', new \DateTime())
            ->setParameter('open', 'open')
            ->orderBy('t.dueDate', 'ASC');

        if (null !== $ownerId) {
            $qb->andWhere('t.owner = :ownerId')
                ->setParameter('ownerId', $ownerId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Task[]
     */
    public function findDueTodayTasks(?int $ownerId = null): array
    {
        $start = new \DateTime('today');
        $end   = new \DateTime('tomorrow');

        $qb = $this->createQueryBuilder('t')
            ->where('t.dueDate >= :start')
            ->andWhere('t.dueDate < :end')
            ->andWhere('t.status = :open')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('open', 'open')
            ->orderBy('t.dueDate', 'ASC');

        if (null !== $ownerId) {
            $qb->andWhere('t.owner = :ownerId')
                ->setParameter('ownerId', $ownerId);
        }

        return $qb->getQuery()->getResult();
    }
}
