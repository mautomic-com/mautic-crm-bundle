<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Note>
 */
class NoteRepository extends CommonRepository
{
    public function getTableAlias(): string
    {
        return 'n';
    }

    /**
     * @return array<array<string>>
     */
    protected function getDefaultOrder(): array
    {
        return [
            [$this->getTableAlias().'.dateAdded', 'DESC'],
        ];
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     *
     * @return array{string, array<mixed>}
     */
    protected function addCatchAllWhereClause($q, $filter): array
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, ['n.text']);
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
     * Return notes directly linked to the deal, plus notes linked to the deal's contact
     * but not directly to any deal (AC-7).
     *
     * @return Note[]
     */
    public function findByDeal(int $dealId, ?int $contactId = null): array
    {
        $qb = $this->createQueryBuilder('n');

        if (null !== $contactId) {
            $qb->where(
                $qb->expr()->orX(
                    $qb->expr()->eq('n.deal', ':dealId'),
                    $qb->expr()->andX(
                        $qb->expr()->eq('n.contact', ':contactId'),
                        $qb->expr()->isNull('n.deal')
                    )
                )
            )
            ->setParameter('dealId', $dealId)
            ->setParameter('contactId', $contactId);
        } else {
            $qb->where('n.deal = :dealId')
                ->setParameter('dealId', $dealId);
        }

        return $qb->orderBy('n.dateAdded', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
