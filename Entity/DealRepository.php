<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Deal>
 */
class DealRepository extends CommonRepository
{
    public function getTableAlias(): string
    {
        return 'd';
    }

    public function getEntities(array $args = [])
    {
        $alias = $this->getTableAlias();

        $q = $this->_em
            ->createQueryBuilder()
            ->select($alias)
            ->from(Deal::class, $alias, $alias.'.id');

        if (empty($args['iterable_mode'])) {
            $q->leftJoin($alias.'.category', 'c');
            $q->leftJoin($alias.'.pipeline', 'p');
            $q->leftJoin($alias.'.stage', 'st');
        }

        $args['qb'] = $q;

        return parent::getEntities($args);
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
        return $this->addStandardCatchAllWhereClause($q, $filter, ['d.name']);
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
     * @return Deal[]
     */
    public function getDealsForContact(int $contactId, ?int $pipelineId = null): array
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('d')
            ->from(Deal::class, 'd')
            ->where('d.contact = :contactId')
            ->andWhere('d.isPublished = :published')
            ->setParameter('contactId', $contactId)
            ->setParameter('published', true);

        if (null !== $pipelineId) {
            $qb->andWhere('d.pipeline = :pipelineId')
                ->setParameter('pipelineId', $pipelineId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Deal[]
     */
    public function getDealsForBoard(int $pipelineId): array
    {
        return $this->_em->createQueryBuilder()
            ->select('d', 'st', 'c', 'o')
            ->from(Deal::class, 'd')
            ->leftJoin('d.stage', 'st')
            ->leftJoin('d.contact', 'c')
            ->leftJoin('d.owner', 'o')
            ->where('d.pipeline = :pipelineId')
            ->andWhere('d.isPublished = :published')
            ->setParameter('pipelineId', $pipelineId)
            ->setParameter('published', true)
            ->orderBy('st.order', 'ASC')
            ->addOrderBy('d.dateAdded', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
