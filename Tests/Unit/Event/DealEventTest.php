<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Event;

use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Event\DealEvent;
use PHPUnit\Framework\TestCase;

class DealEventTest extends TestCase
{
    public function testGetDeal(): void
    {
        $deal = new Deal();
        $deal->setName('Test Deal');
        $event = new DealEvent($deal);

        $this->assertSame($deal, $event->getDeal());
    }

    public function testIsNewDefaultsFalse(): void
    {
        $deal  = new Deal();
        $event = new DealEvent($deal);

        $this->assertFalse($event->isNew());
    }

    public function testIsNewTrue(): void
    {
        $deal  = new Deal();
        $event = new DealEvent($deal, true);

        $this->assertTrue($event->isNew());
    }

    public function testEntityIsAccessible(): void
    {
        $deal = new Deal();
        $deal->setName('Event Deal');
        $event = new DealEvent($deal);

        $this->assertSame($deal, $event->getDeal());
        $this->assertSame('Event Deal', $event->getDeal()->getName());
    }
}
