<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;

class DealEvent extends CommonEvent
{
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
}
