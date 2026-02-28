<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Entity;

use MauticPlugin\MautomicCrmBundle\Entity\Task;
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{
    public function testGetSetTitle(): void
    {
        $task = new Task();
        $task->setTitle('Follow up call');
        $this->assertSame('Follow up call', $task->getTitle());
    }

    public function testDefaultStatus(): void
    {
        $task = new Task();
        $this->assertSame('open', $task->getStatus());
    }

    public function testDefaultPriority(): void
    {
        $task = new Task();
        $this->assertSame('normal', $task->getPriority());
    }

    public function testGetSetDueDate(): void
    {
        $task = new Task();
        $date = new \DateTime('2026-03-15 14:00:00');
        $task->setDueDate($date);
        $this->assertSame($date, $task->getDueDate());
    }

    public function testGetNameReturnsTitle(): void
    {
        $task = new Task();
        $task->setTitle('My Task');
        $this->assertSame('My Task', $task->getName());
    }
}
