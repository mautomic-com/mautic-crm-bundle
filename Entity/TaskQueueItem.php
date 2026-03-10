<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class TaskQueueItem
{
    private ?int $id = null;

    private ?TaskQueue $queue = null;

    private ?Task $task = null;

    private int $itemOrder = 0;

    private string $status = 'pending';

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('mautomic_task_queue_items')
            ->setCustomRepositoryClass(TaskQueueItemRepository::class)
            ->addIndex(['status'], 'mautomic_tqi_status');

        $builder->addId();

        $builder->createManyToOne('queue', TaskQueue::class)
            ->inversedBy('items')
            ->addJoinColumn('queue_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->createManyToOne('task', Task::class)
            ->addJoinColumn('task_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->createField('itemOrder', 'integer')
            ->columnName('item_order')
            ->build();

        $builder->addField('status', 'string');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQueue(): ?TaskQueue
    {
        return $this->queue;
    }

    public function setQueue(?TaskQueue $queue): self
    {
        $this->queue = $queue;

        return $this;
    }

    public function getTask(): ?Task
    {
        return $this->task;
    }

    public function setTask(?Task $task): self
    {
        $this->task = $task;

        return $this;
    }

    public function getItemOrder(): int
    {
        return $this->itemOrder;
    }

    public function setItemOrder(int $itemOrder): self
    {
        $this->itemOrder = $itemOrder;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }
}
