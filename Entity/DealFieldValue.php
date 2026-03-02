<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class DealFieldValue
{
    private ?int $id = null;

    private ?Deal $deal = null;

    private ?DealField $field = null;

    private ?string $value = null;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('mautomic_deal_field_values')
            ->setCustomRepositoryClass(DealFieldValueRepository::class)
            ->addUniqueConstraint(['deal_id', 'field_id'], 'mautomic_deal_field_value_uniq');

        $builder->addId();

        $builder->createManyToOne('deal', Deal::class)
            ->addJoinColumn('deal_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->createManyToOne('field', DealField::class)
            ->addJoinColumn('field_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addNullableField('value', 'text');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDeal(): ?Deal
    {
        return $this->deal;
    }

    public function setDeal(?Deal $deal): self
    {
        $this->deal = $deal;

        return $this;
    }

    public function getField(): ?DealField
    {
        return $this->field;
    }

    public function setField(?DealField $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }
}
