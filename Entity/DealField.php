<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class DealField extends FormEntity
{
    public const SUPPORTED_TYPES = ['text', 'textarea', 'number', 'select', 'date', 'boolean'];

    private ?int $id = null;

    private ?string $label = null;

    private ?string $alias = null;

    private ?string $type = null;

    private bool $isRequired = false;

    private ?string $fieldGroup = null;

    private int $fieldOrder = 0;

    private ?string $properties = null;

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('mautomic_deal_fields')
            ->setCustomRepositoryClass(DealFieldRepository::class)
            ->addIndex(['alias'], 'mautomic_deal_field_alias')
            ->addUniqueConstraint(['alias'], 'mautomic_deal_field_alias_uniq');

        $builder->addIdColumns('label', '');

        $builder->addNullableField('alias', 'string');

        $builder->addField('type', 'string');

        $builder->createField('isRequired', 'boolean')
            ->columnName('is_required')
            ->build();

        $builder->createField('fieldGroup', 'string')
            ->columnName('field_group')
            ->nullable()
            ->build();

        $builder->createField('fieldOrder', 'integer')
            ->columnName('field_order')
            ->build();

        $builder->addNullableField('properties', 'text');
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('label', new NotBlank([
            'message' => 'mautomic_crm.deal_field.name.required',
        ]));

        $metadata->addPropertyConstraint('alias', new NotBlank([
            'message' => 'mautomic_crm.deal_field.alias.required',
        ]));

        $metadata->addPropertyConstraint('type', new NotBlank());
        $metadata->addPropertyConstraint('type', new Choice([
            'choices' => self::SUPPORTED_TYPES,
        ]));
    }

    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('dealField')
            ->addListProperties(['id', 'label', 'alias', 'type'])
            ->addProperties([
                'isRequired',
                'fieldGroup',
                'fieldOrder',
                'properties',
            ])
            ->build();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->isChanged('label', $label);
        $this->label = $label;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->label;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(?string $alias): self
    {
        $this->isChanged('alias', $alias);
        $this->alias = $alias;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->isChanged('type', $type);
        $this->type = $type;

        return $this;
    }

    public function getIsRequired(): bool
    {
        return $this->isRequired;
    }

    public function setIsRequired(bool $isRequired): self
    {
        $this->isChanged('isRequired', $isRequired);
        $this->isRequired = $isRequired;

        return $this;
    }

    public function getFieldGroup(): ?string
    {
        return $this->fieldGroup;
    }

    public function setFieldGroup(?string $fieldGroup): self
    {
        $this->isChanged('fieldGroup', $fieldGroup);
        $this->fieldGroup = $fieldGroup;

        return $this;
    }

    public function getFieldOrder(): int
    {
        return $this->fieldOrder;
    }

    public function setFieldOrder(?int $fieldOrder): self
    {
        $fieldOrder ??= 0;
        $this->isChanged('fieldOrder', $fieldOrder);
        $this->fieldOrder = $fieldOrder;

        return $this;
    }

    public function getProperties(): ?string
    {
        return $this->properties;
    }

    public function setProperties(?string $properties): self
    {
        $this->isChanged('properties', $properties);
        $this->properties = $properties;

        return $this;
    }
}
