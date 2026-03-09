<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<TaskQueueItem>
 */
class TaskQueueItemRepository extends CommonRepository
{
    public function getTableAlias(): string
    {
        return 'tqi';
    }

    /**
     * @return array<array<string>>
     */
    protected function getDefaultOrder(): array
    {
        return [
            [$this->getTableAlias().'.itemOrder', 'ASC'],
        ];
    }

    /**
     * @return TaskQueueItem[]
     */
    public function findByQueue(int $queueId, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('tqi')
            ->join('tqi.task', 't')
            ->where('tqi.queue = :queueId')
            ->setParameter('queueId', $queueId)
            ->orderBy('tqi.itemOrder', 'ASC');

        if (null !== $status) {
            $qb->andWhere('tqi.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    public function getNextPendingItem(int $queueId): ?TaskQueueItem
    {
        return $this->createQueryBuilder('tqi')
            ->join('tqi.task', 't')
            ->where('tqi.queue = :queueId')
            ->andWhere('tqi.status = :status')
            ->setParameter('queueId', $queueId)
            ->setParameter('status', 'pending')
            ->orderBy('tqi.itemOrder', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array{total: int, completed: int, skipped: int, pending: int}
     */
    public function getQueueStats(int $queueId): array
    {
        $items = $this->createQueryBuilder('tqi')
            ->select('tqi.status, COUNT(tqi.id) as cnt')
            ->where('tqi.queue = :queueId')
            ->setParameter('queueId', $queueId)
            ->groupBy('tqi.status')
            ->getQuery()
            ->getArrayResult();

        $stats = ['total' => 0, 'completed' => 0, 'skipped' => 0, 'pending' => 0];
        foreach ($items as $row) {
            $count = (int) $row['cnt'];
            $stats['total'] += $count;
            if (isset($stats[$row['status']])) {
                $stats[$row['status']] = $count;
            }
        }

        return $stats;
    }
}
