<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;

class DealEvent extends CommonEvent
{
    private ?int $previousStageId = null;

    private ?int $newStageId = null;

    public function __construct(
        Deal $deal,
        bool $isNew = false,
    ) {
        $this->entity = $deal;
        $this->isNew  = $isNew;
    }

    public function getDeal(): Deal
    {
        return $this->entity;
    }

    /**
     * @return array<string, mixed>
     */
    public function getChanges(): array
    {
        $changes = parent::getChanges();

        return \is_array($changes) ? $changes : [];
    }

    public function getPreviousStageId(): ?int
    {
        return $this->previousStageId;
    }

    public function setPreviousStageId(?int $previousStageId): self
    {
        $this->previousStageId = $previousStageId;

        return $this;
    }

    public function getNewStageId(): ?int
    {
        return $this->newStageId;
    }

    public function setNewStageId(?int $newStageId): self
    {
        $this->newStageId = $newStageId;

        return $this;
    }
}
