<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Deal extends FormEntity
{
    private ?int $id = null;

    private ?string $name = null;

    private ?string $description = null;

    private ?string $amount = null;

    private ?string $currency = null;

    private ?\DateTimeInterface $closeDate = null;

    private ?Pipeline $pipeline = null;

    private ?Stage $stage = null;

    private ?Lead $contact = null;

    private ?Company $company = null;

    private ?User $owner = null;

    private ?\Mautic\CategoryBundle\Entity\Category $category = null;

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('mautomic_deals')
            ->setCustomRepositoryClass(DealRepository::class)
            ->addIndex(['name'], 'mautomic_deal_name')
            ->addIndex(['pipeline_id', 'stage_id'], 'mautomic_deal_pipeline_stage');

        $builder->addIdColumns();

        $builder->addCategory();

        $builder->createField('amount', 'decimal')
            ->precision(15)
            ->scale(2)
            ->nullable()
            ->build();

        $builder->addNullableField('currency', 'string');

        $builder->createField('closeDate', 'date')
            ->columnName('close_date')
            ->nullable()
            ->build();

        $builder->createManyToOne('pipeline', Pipeline::class)
            ->addJoinColumn('pipeline_id', 'id', false)
            ->build();

        $builder->createManyToOne('stage', Stage::class)
            ->addJoinColumn('stage_id', 'id', false)
            ->build();

        $builder->createManyToOne('contact', Lead::class)
            ->addJoinColumn('contact_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createManyToOne('company', Company::class)
            ->addJoinColumn('company_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createManyToOne('owner', User::class)
            ->addJoinColumn('owner_id', 'id', true, false, 'SET NULL')
            ->build();
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('name', new NotBlank([
            'message' => 'mautomic_crm.deal.name.required',
        ]));
    }

    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('deal')
            ->addListProperties(['id', 'name', 'amount', 'stage'])
            ->addProperties([
                'description',
                'currency',
                'closeDate',
                'pipeline',
                'contact',
                'company',
                'owner',
                'category',
            ])
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): self
    {
        $this->isChanged('amount', $amount);
        $this->amount = $amount;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->isChanged('currency', $currency);
        $this->currency = $currency;

        return $this;
    }

    public function getCloseDate(): ?\DateTimeInterface
    {
        return $this->closeDate;
    }

    public function setCloseDate(?\DateTimeInterface $closeDate): self
    {
        $this->isChanged('closeDate', $closeDate);
        $this->closeDate = $closeDate;

        return $this;
    }

    public function getPipeline(): ?Pipeline
    {
        return $this->pipeline;
    }

    public function setPipeline(?Pipeline $pipeline): self
    {
        $this->isChanged('pipeline', $pipeline);
        $this->pipeline = $pipeline;

        return $this;
    }

    public function getStage(): ?Stage
    {
        return $this->stage;
    }

    public function setStage(?Stage $stage): self
    {
        $this->isChanged('stage', $stage);
        $this->stage = $stage;

        return $this;
    }

    public function getContact(): ?Lead
    {
        return $this->contact;
    }

    public function setContact(?Lead $contact): self
    {
        $this->isChanged('contact', $contact);
        $this->contact = $contact;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->isChanged('company', $company);
        $this->company = $company;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->isChanged('owner', $owner);
        $this->owner = $owner;

        return $this;
    }

    public function getCategory(): ?\Mautic\CategoryBundle\Entity\Category
    {
        return $this->category;
    }

    public function setCategory(?\Mautic\CategoryBundle\Entity\Category $category): self
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }
}
