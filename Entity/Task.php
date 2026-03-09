<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Task extends FormEntity
{
    private ?int $id = null;

    private ?string $title = null;

    private ?string $description = null;

    private ?\DateTimeInterface $dueDate = null;

    private string $status = 'open';

    private string $priority = 'normal';

    private ?Deal $deal = null;

    private ?Lead $contact = null;

    private ?User $owner = null;

    private ?\DateTimeInterface $reminderDate = null;

    private bool $reminderSent = false;

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('mautomic_tasks')
            ->setCustomRepositoryClass(TaskRepository::class)
            ->addIndex(['status'], 'mautomic_task_status')
            ->addIndex(['due_date'], 'mautomic_task_due_date')
            ->addIndex(['owner_id'], 'mautomic_task_owner');

        $builder->addId();

        $builder->addField('title', 'string');

        $builder->addNullableField('description', 'text');

        $builder->createField('dueDate', 'datetime')
            ->columnName('due_date')
            ->nullable()
            ->build();

        $builder->addField('status', 'string');

        $builder->addField('priority', 'string');

        $builder->createField('reminderDate', 'datetime')
            ->columnName('reminder_date')
            ->nullable()
            ->build();

        $builder->createField('reminderSent', 'boolean')
            ->columnName('reminder_sent')
            ->build();

        $builder->createManyToOne('deal', Deal::class)
            ->addJoinColumn('deal_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createManyToOne('contact', Lead::class)
            ->addJoinColumn('contact_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createManyToOne('owner', User::class)
            ->addJoinColumn('owner_id', 'id', true, false, 'SET NULL')
            ->build();
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('title', new NotBlank([
            'message' => 'mautomic_crm.task.title.required',
        ]));
    }

    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('task')
            ->addListProperties(['id', 'title', 'dueDate', 'status', 'priority'])
            ->addProperties(['description', 'deal', 'contact', 'owner', 'reminderDate', 'reminderSent'])
            ->build();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->isChanged('title', $title);
        $this->title = $title;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->title;
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

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeInterface $dueDate): self
    {
        $this->isChanged('dueDate', $dueDate);
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->isChanged('status', $status);
        $this->status = $status;

        return $this;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): self
    {
        $this->isChanged('priority', $priority);
        $this->priority = $priority;

        return $this;
    }

    public function getDeal(): ?Deal
    {
        return $this->deal;
    }

    public function setDeal(?Deal $deal): self
    {
        $this->isChanged('deal', $deal);
        $this->deal = $deal;

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

    public function getReminderDate(): ?\DateTimeInterface
    {
        return $this->reminderDate;
    }

    public function setReminderDate(?\DateTimeInterface $reminderDate): self
    {
        $this->isChanged('reminderDate', $reminderDate);
        $this->reminderDate = $reminderDate;

        return $this;
    }

    public function isReminderSent(): bool
    {
        return $this->reminderSent;
    }

    public function getReminderSent(): bool
    {
        return $this->reminderSent;
    }

    public function setReminderSent(bool $reminderSent): self
    {
        $this->isChanged('reminderSent', $reminderSent);
        $this->reminderSent = $reminderSent;

        return $this;
    }
}
