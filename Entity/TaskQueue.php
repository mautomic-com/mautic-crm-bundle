<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class TaskQueue extends FormEntity
{
    private ?int $id = null;

    private ?string $name = null;

    private ?string $description = null;

    private ?User $owner = null;

    private string $sortOrder = 'due_date';

    private bool $isShared = false;

    /** @var string|null JSON-encoded filter criteria */
    private ?string $filters = null;

    /** @var Collection<int, TaskQueueItem> */
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('mautomic_task_queues')
            ->setCustomRepositoryClass(TaskQueueRepository::class)
            ->addIndex(['owner_id'], 'mautomic_task_queue_owner');

        $builder->addId();

        $builder->addField('name', 'string');

        $builder->addNullableField('description', 'text');

        $builder->createField('sortOrder', 'string')
            ->columnName('sort_order')
            ->build();

        $builder->createField('isShared', 'boolean')
            ->columnName('is_shared')
            ->build();

        $builder->addNullableField('filters', 'text');

        $builder->createManyToOne('owner', User::class)
            ->addJoinColumn('owner_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createOneToMany('items', TaskQueueItem::class)
            ->setIndexBy('id')
            ->mappedBy('queue')
            ->fetchExtraLazy()
            ->build();
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('name', new NotBlank([
            'message' => 'mautomic_crm.task_queue.name.required',
        ]));
    }

    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('taskQueue')
            ->addListProperties(['id', 'name', 'sortOrder', 'isShared'])
            ->addProperties(['description', 'owner', 'filters'])
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

    public function getSortOrder(): string
    {
        return $this->sortOrder;
    }

    public function setSortOrder(string $sortOrder): self
    {
        $this->isChanged('sortOrder', $sortOrder);
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function isShared(): bool
    {
        return $this->isShared;
    }

    public function setIsShared(bool $isShared): self
    {
        $this->isChanged('isShared', $isShared);
        $this->isShared = $isShared;

        return $this;
    }

    public function getFilters(): ?string
    {
        return $this->filters;
    }

    public function setFilters(?string $filters): self
    {
        $this->isChanged('filters', $filters);
        $this->filters = $filters;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDecodedFilters(): ?array
    {
        if (null === $this->filters) {
            return null;
        }

        $decoded = json_decode($this->filters, true);

        return \is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<string, mixed>|null $filters
     */
    public function setDecodedFilters(?array $filters): self
    {
        $this->setFilters(null !== $filters ? json_encode($filters, \JSON_THROW_ON_ERROR) : null);

        return $this;
    }

    /**
     * @return Collection<int, TaskQueueItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(TaskQueueItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setQueue($this);
        }

        return $this;
    }

    public function removeItem(TaskQueueItem $item): self
    {
        $this->items->removeElement($item);

        return $this;
    }
}
