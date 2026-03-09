<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Entity;

use MauticPlugin\MautomicCrmBundle\Entity\Task;
use MauticPlugin\MautomicCrmBundle\Entity\TaskQueue;
use MauticPlugin\MautomicCrmBundle\Entity\TaskQueueItem;
use PHPUnit\Framework\TestCase;

class TaskQueueItemTest extends TestCase
{
    public function testDefaultStatus(): void
    {
        $item = new TaskQueueItem();
        $this->assertSame('pending', $item->getStatus());
    }

    public function testDefaultOrder(): void
    {
        $item = new TaskQueueItem();
        $this->assertSame(0, $item->getItemOrder());
    }

    public function testGetSetQueue(): void
    {
        $item  = new TaskQueueItem();
        $queue = new TaskQueue();
        $item->setQueue($queue);
        $this->assertSame($queue, $item->getQueue());
    }

    public function testGetSetTask(): void
    {
        $item = new TaskQueueItem();
        $task = new Task();
        $task->setTitle('Test');
        $item->setTask($task);
        $this->assertSame($task, $item->getTask());
    }

    public function testGetSetStatus(): void
    {
        $item = new TaskQueueItem();
        $item->setStatus('completed');
        $this->assertSame('completed', $item->getStatus());
    }

    public function testGetSetItemOrder(): void
    {
        $item = new TaskQueueItem();
        $item->setItemOrder(5);
        $this->assertSame(5, $item->getItemOrder());
    }
}
