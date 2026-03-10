<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Entity;

use MauticPlugin\MautomicCrmBundle\Entity\TaskQueue;
use MauticPlugin\MautomicCrmBundle\Entity\TaskQueueItem;
use PHPUnit\Framework\TestCase;

class TaskQueueTest extends TestCase
{
    public function testGetSetName(): void
    {
        $queue = new TaskQueue();
        $queue->setName('Morning follow-ups');
        $this->assertSame('Morning follow-ups', $queue->getName());
    }

    public function testDefaultSortOrder(): void
    {
        $queue = new TaskQueue();
        $this->assertSame('due_date', $queue->getSortOrder());
    }

    public function testDefaultIsShared(): void
    {
        $queue = new TaskQueue();
        $this->assertFalse($queue->isShared());
    }

    public function testGetSetDescription(): void
    {
        $queue = new TaskQueue();
        $queue->setDescription('High priority tasks');
        $this->assertSame('High priority tasks', $queue->getDescription());
    }

    public function testGetSetSortOrder(): void
    {
        $queue = new TaskQueue();
        $queue->setSortOrder('priority');
        $this->assertSame('priority', $queue->getSortOrder());
    }

    public function testGetSetIsShared(): void
    {
        $queue = new TaskQueue();
        $queue->setIsShared(true);
        $this->assertTrue($queue->isShared());
    }

    public function testFiltersJsonEncoding(): void
    {
        $queue   = new TaskQueue();
        $filters = ['status' => 'open', 'priority' => 'high'];
        $queue->setDecodedFilters($filters);

        $this->assertSame($filters, $queue->getDecodedFilters());
    }

    public function testFiltersNullHandling(): void
    {
        $queue = new TaskQueue();
        $this->assertNull($queue->getFilters());
        $this->assertNull($queue->getDecodedFilters());
    }

    public function testAddRemoveItem(): void
    {
        $queue = new TaskQueue();
        $item  = new TaskQueueItem();

        $queue->addItem($item);
        $this->assertCount(1, $queue->getItems());
        $this->assertSame($queue, $item->getQueue());

        $queue->removeItem($item);
        $this->assertCount(0, $queue->getItems());
    }

    public function testCloneResetsId(): void
    {
        $queue = new TaskQueue();
        $queue->setName('Original');

        $reflection = new \ReflectionClass($queue);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($queue, 42);

        $clone = clone $queue;
        $this->assertNull($clone->getId());
        $this->assertSame('Original', $clone->getName());
    }
}
