<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<DealFieldValue>
 */
class DealFieldValueRepository extends CommonRepository
{
    public function getTableAlias(): string
    {
        return 'dfv';
    }

    /**
     * @return array<string, string|null>
     */
    public function getValuesForDeal(int $dealId): array
    {
        $results = $this->_em->createQueryBuilder()
            ->select('dfv.value', 'f.alias')
            ->from(DealFieldValue::class, 'dfv')
            ->join('dfv.field', 'f')
            ->where('dfv.deal = :dealId')
            ->setParameter('dealId', $dealId)
            ->getQuery()
            ->getArrayResult();

        $values = [];
        foreach ($results as $row) {
            $values[$row['alias']] = $row['value'];
        }

        return $values;
    }

    public function getValueForDealAndField(int $dealId, int $fieldId): ?DealFieldValue
    {
        return $this->findOneBy(['deal' => $dealId, 'field' => $fieldId]);
    }

    /**
     * @param array<string, string|null> $fieldValues alias => value
     */
    public function saveValues(Deal $deal, array $fieldValues): void
    {
        $fieldRepo = $this->_em->getRepository(DealField::class);

        foreach ($fieldValues as $alias => $value) {
            $field = $fieldRepo->findOneBy(['alias' => $alias]);
            if (null === $field) {
                continue;
            }

            $existing = $this->findOneBy(['deal' => $deal->getId(), 'field' => $field->getId()]);

            if (null === $existing) {
                $existing = new DealFieldValue();
                $existing->setDeal($deal);
                $existing->setField($field);
            }

            $existing->setValue($value);
            $this->_em->persist($existing);
        }

        $this->_em->flush();
    }
}
