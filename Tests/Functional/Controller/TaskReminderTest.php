<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MautomicCrmBundle\Entity\Task;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TaskReminderTest extends MauticMysqlTestCase
{
    public function testNewTaskFormHasReminderField(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/mautomic/tasks/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertGreaterThan(0, $crawler->filter('input[id="task_reminderDate"]')->count(), 'Reminder date field should exist');
    }

    public function testTaskViewShowsReminderDate(): void
    {
        $task = new Task();
        $task->setTitle('Task with Reminder');
        $task->setReminderDate(new \DateTime('2026-03-15 09:00:00'));
        $task->setIsPublished(true);
        $this->em->persist($task);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/tasks/view/'.$task->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('2026-03-15 09:00', $this->client->getResponse()->getContent());
    }

    public function testTaskViewShowsReminderSentBadge(): void
    {
        $task = new Task();
        $task->setTitle('Sent Reminder Task');
        $task->setReminderDate(new \DateTime('2026-03-10 09:00:00'));
        $task->setReminderSent(true);
        $task->setIsPublished(true);
        $this->em->persist($task);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/tasks/view/'.$task->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Sent', $this->client->getResponse()->getContent());
    }

    public function testReminderFieldPersists(): void
    {
        $task = new Task();
        $task->setTitle('Persistent Reminder');
        $task->setReminderDate(new \DateTime('2026-04-01 14:00:00'));
        $task->setIsPublished(true);
        $this->em->persist($task);
        $this->em->flush();

        $this->em->clear();

        $loaded = $this->em->getRepository(Task::class)->find($task->getId());
        $this->assertNotNull($loaded);
        $this->assertNotNull($loaded->getReminderDate());
        $this->assertSame('2026-04-01', $loaded->getReminderDate()->format('Y-m-d'));
        $this->assertFalse($loaded->isReminderSent());
    }
}
