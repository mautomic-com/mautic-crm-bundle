<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Stage extends FormEntity
{
    private ?int $id = null;

    private ?string $name = null;

    private ?Pipeline $pipeline = null;

    private int $order = 0;

    private int $probability = 0;

    private string $type = 'open';

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('mautomic_stages')
            ->setCustomRepositoryClass(StageRepository::class)
            ->addIndex(['pipeline_id', 'stage_order'], 'mautomic_stage_pipeline_order');

        $builder->addId();

        $builder->addField('name', 'string');

        $builder->createManyToOne('pipeline', Pipeline::class)
            ->inversedBy('stages')
            ->addJoinColumn('pipeline_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->createField('order', 'integer')
            ->columnName('stage_order')
            ->build();

        $builder->addField('probability', 'integer');

        $builder->createField('type', 'string')
            ->columnName('stage_type')
            ->build();
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('name', new NotBlank([
            'message' => 'mautomic_crm.stage.name.required',
        ]));

        $metadata->addPropertyConstraint('probability', new Range([
            'min'               => 0,
            'max'               => 100,
            'notInRangeMessage' => 'mautomic_crm.stage.probability.range',
        ]));
    }

    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('stage')
            ->addListProperties(['id', 'name', 'order', 'probability', 'type'])
            ->addProperties(['pipeline'])
            ->build();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    public function getPipeline(): ?Pipeline
    {
        return $this->pipeline;
    }

    public function setPipeline(?Pipeline $pipeline): self
    {
        $this->pipeline = $pipeline;

        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(?int $order): self
    {
        $order ??= 0;
        $this->isChanged('order', $order);
        $this->order = $order;

        return $this;
    }

    public function getProbability(): int
    {
        return $this->probability;
    }

    public function setProbability(?int $probability): self
    {
        $probability ??= 0;
        $this->isChanged('probability', $probability);
        $this->probability = $probability;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->isChanged('type', $type);
        $this->type = $type;

        return $this;
    }
}
