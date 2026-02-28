<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Entity;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\Task;
use PHPUnit\Framework\TestCase;

class TaskAdvancedTest extends TestCase
{
    private Task $task;

    protected function setUp(): void
    {
        $this->task = new Task();
    }

    public function testNewTaskDefaults(): void
    {
        $this->assertNull($this->task->getId());
        $this->assertNull($this->task->getTitle());
        $this->assertNull($this->task->getDescription());
        $this->assertNull($this->task->getDueDate());
        $this->assertSame('open', $this->task->getStatus());
        $this->assertSame('normal', $this->task->getPriority());
        $this->assertNull($this->task->getDeal());
        $this->assertNull($this->task->getContact());
        $this->assertNull($this->task->getOwner());
    }

    public function testSetAllStatuses(): void
    {
        $this->task->setStatus('open');
        $this->assertSame('open', $this->task->getStatus());

        $this->task->setStatus('completed');
        $this->assertSame('completed', $this->task->getStatus());
    }

    public function testSetAllPriorities(): void
    {
        foreach (['low', 'normal', 'high'] as $priority) {
            $this->task->setPriority($priority);
            $this->assertSame($priority, $this->task->getPriority());
        }
    }

    public function testLinkToDeal(): void
    {
        $deal = new Deal();
        $deal->setName('Related Deal');
        $this->task->setDeal($deal);
        $this->assertSame($deal, $this->task->getDeal());
    }

    public function testLinkToContact(): void
    {
        $contact = new Lead();
        $this->task->setContact($contact);
        $this->assertSame($contact, $this->task->getContact());
    }

    public function testSetOwner(): void
    {
        $user = new User();
        $this->task->setOwner($user);
        $this->assertSame($user, $this->task->getOwner());
    }

    public function testGetNameReturnsTitle(): void
    {
        $this->task->setTitle('Follow up call');
        $this->assertSame('Follow up call', $this->task->getName());
    }

    public function testSetDescription(): void
    {
        $this->task->setDescription('Detailed description');
        $this->assertSame('Detailed description', $this->task->getDescription());
    }

    public function testCloneResetsId(): void
    {
        $this->task->setTitle('Clone me');
        $cloned = clone $this->task;
        $this->assertNull($cloned->getId());
        $this->assertSame('Clone me', $cloned->getTitle());
    }

    public function testChangeTracking(): void
    {
        $this->task->setTitle('Original');
        $this->task->setTitle('Changed');
        $changes = $this->task->getChanges();
        $this->assertArrayHasKey('title', $changes);
    }

    public function testFullTaskWorkflow(): void
    {
        $deal = new Deal();
        $deal->setName('Enterprise Deal');

        $owner = new User();

        $this->task->setTitle('Send proposal');
        $this->task->setDescription('Send the final proposal');
        $this->task->setPriority('high');
        $this->task->setDueDate(new \DateTime('2026-03-15'));
        $this->task->setDeal($deal);
        $this->task->setOwner($owner);

        $this->assertSame('Send proposal', $this->task->getTitle());
        $this->assertSame('high', $this->task->getPriority());
        $this->assertSame('open', $this->task->getStatus());
        $this->assertSame($deal, $this->task->getDeal());

        $this->task->setStatus('completed');
        $this->assertSame('completed', $this->task->getStatus());
    }
}
