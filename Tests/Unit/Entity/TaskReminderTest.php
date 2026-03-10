<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Entity;

use MauticPlugin\MautomicCrmBundle\Entity\Task;
use PHPUnit\Framework\TestCase;

class TaskReminderTest extends TestCase
{
    public function testDefaultReminderDate(): void
    {
        $task = new Task();
        $this->assertNull($task->getReminderDate());
    }

    public function testDefaultReminderSent(): void
    {
        $task = new Task();
        $this->assertFalse($task->isReminderSent());
    }

    public function testGetSetReminderDate(): void
    {
        $task = new Task();
        $date = new \DateTime('2026-03-15 09:00:00');
        $task->setReminderDate($date);
        $this->assertSame($date, $task->getReminderDate());
    }

    public function testSetReminderDateNull(): void
    {
        $task = new Task();
        $task->setReminderDate(new \DateTime());
        $task->setReminderDate(null);
        $this->assertNull($task->getReminderDate());
    }

    public function testGetSetReminderSent(): void
    {
        $task = new Task();
        $task->setReminderSent(true);
        $this->assertTrue($task->isReminderSent());
    }
}
