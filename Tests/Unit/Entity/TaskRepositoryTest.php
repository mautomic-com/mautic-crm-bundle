<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Entity;

use MauticPlugin\MautomicCrmBundle\Entity\Task;
use PHPUnit\Framework\TestCase;

class TaskRepositoryTest extends TestCase
{
    public function testTaskDealAssociation(): void
    {
        $task = new Task();
        $this->assertNull($task->getDeal());

        $deal = $this->createMock(\MauticPlugin\MautomicCrmBundle\Entity\Deal::class);
        $deal->method('getId')->willReturn(42);

        $task->setDeal($deal);
        $this->assertSame($deal, $task->getDeal());
        $this->assertNotNull($task->getDeal());
        $this->assertSame(42, $task->getDeal()->getId());
    }

    public function testTaskContactAssociation(): void
    {
        $task = new Task();
        $this->assertNull($task->getContact());

        $contact = $this->createMock(\Mautic\LeadBundle\Entity\Lead::class);
        $contact->method('getId')->willReturn(7);

        $task->setContact($contact);
        $this->assertSame($contact, $task->getContact());
    }

    public function testTaskDealCanBeCleared(): void
    {
        $deal = $this->createMock(\MauticPlugin\MautomicCrmBundle\Entity\Deal::class);

        $task = new Task();
        $task->setDeal($deal);
        $this->assertNotNull($task->getDeal());

        $task->setDeal(null);
        $this->assertNull($task->getDeal());
    }
}
