<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<DealField>
 */
class DealFieldRepository extends CommonRepository
{
    public function getTableAlias(): string
    {
        return 'df';
    }

    /**
     * @return DealField[]
     */
    public function getPublishedFields(): array
    {
        return $this->_em->createQueryBuilder()
            ->select('df')
            ->from(DealField::class, 'df')
            ->where('df.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('df.fieldGroup', 'ASC')
            ->addOrderBy('df.fieldOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getFieldByAlias(string $alias): ?DealField
    {
        return $this->findOneBy(['alias' => $alias]);
    }

    /**
     * @return array<array<string>>
     */
    protected function getDefaultOrder(): array
    {
        return [
            [$this->getTableAlias().'.fieldOrder', 'ASC'],
        ];
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     *
     * @return array{string, array<mixed>}
     */
    protected function addCatchAllWhereClause($q, $filter): array
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, ['df.label', 'df.alias']);
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
}
