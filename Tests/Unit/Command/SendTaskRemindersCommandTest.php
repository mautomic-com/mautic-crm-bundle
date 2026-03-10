<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Command;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\MautomicCrmBundle\Command\SendTaskRemindersCommand;
use MauticPlugin\MautomicCrmBundle\Entity\Task;
use MauticPlugin\MautomicCrmBundle\Entity\TaskRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class SendTaskRemindersCommandTest extends TestCase
{
    public function testNoRemindersToSend(): void
    {
        $taskRepo = $this->createMock(TaskRepository::class);
        $taskRepo->method('findTasksNeedingReminder')->willReturn([]);

        $notificationModel = $this->createMock(NotificationModel::class);
        $notificationModel->expects($this->never())->method('addNotification');

        $em = $this->createMock(EntityManagerInterface::class);

        $command       = new SendTaskRemindersCommand($taskRepo, $notificationModel, $em);
        $commandTester = $this->createCommandTester($command);

        $commandTester->execute([]);
        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('No task reminders', $commandTester->getDisplay());
    }

    public function testSendsRemindersForTasks(): void
    {
        $owner = $this->createMock(User::class);

        $task = new Task();
        $task->setTitle('Follow up with client');
        $task->setDueDate(new \DateTime('2026-03-15 10:00:00'));
        $task->setReminderDate(new \DateTime('2026-03-14 09:00:00'));
        $task->setOwner($owner);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $taskRepo = $this->createMock(TaskRepository::class);
        $taskRepo->method('findTasksNeedingReminder')->willReturn([$task]);

        $notificationModel = $this->createMock(NotificationModel::class);
        $notificationModel->expects($this->once())
            ->method('addNotification')
            ->with(
                $this->stringContains('Follow up with client'),
                'mautomic_crm',
                false,
                'Follow up with client',
                'ri-task-line',
                null,
                $owner,
            );

        $command       = new SendTaskRemindersCommand($taskRepo, $notificationModel, $em);
        $commandTester = $this->createCommandTester($command);

        $commandTester->execute([]);
        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Sent 1 task reminder', $commandTester->getDisplay());
        $this->assertTrue($task->isReminderSent());
    }

    public function testSkipsTaskWithNoOwner(): void
    {
        $task = new Task();
        $task->setTitle('Orphan task');
        $task->setReminderDate(new \DateTime('2026-03-14 09:00:00'));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $taskRepo = $this->createMock(TaskRepository::class);
        $taskRepo->method('findTasksNeedingReminder')->willReturn([$task]);

        $notificationModel = $this->createMock(NotificationModel::class);
        $notificationModel->expects($this->never())->method('addNotification');

        $command       = new SendTaskRemindersCommand($taskRepo, $notificationModel, $em);
        $commandTester = $this->createCommandTester($command);

        $commandTester->execute([]);
        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertTrue($task->isReminderSent());
    }

    private function createCommandTester(SendTaskRemindersCommand $command): CommandTester
    {
        $app = new Application();
        $app->addCommand($command);

        return new CommandTester($app->find('mautomic:tasks:send-reminders'));
    }
}
