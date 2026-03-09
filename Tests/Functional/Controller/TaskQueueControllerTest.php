<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MautomicCrmBundle\Entity\Task;
use MauticPlugin\MautomicCrmBundle\Entity\TaskQueue;
use MauticPlugin\MautomicCrmBundle\Entity\TaskQueueItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TaskQueueControllerTest extends MauticMysqlTestCase
{
    public function testIndexActionReturns200(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/mautomic/task-queues');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testIndexShowsQueueInList(): void
    {
        $queue = new TaskQueue();
        $queue->setName('Morning Follow-ups');
        $queue->setIsPublished(true);
        $this->em->persist($queue);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/task-queues');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Morning Follow-ups', $this->client->getResponse()->getContent());
    }

    public function testNewActionReturns200(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/mautomic/task-queues/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testNewActionContainsForm(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/mautomic/task-queues/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertGreaterThan(0, $crawler->filter('form[name="task_queue"]')->count(), 'TaskQueue form should exist');
    }

    public function testViewActionReturns200(): void
    {
        $queue = new TaskQueue();
        $queue->setName('Test Queue');
        $queue->setIsPublished(true);
        $this->em->persist($queue);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/task-queues/view/'.$queue->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Test Queue', $this->client->getResponse()->getContent());
    }

    public function testEditActionReturns200(): void
    {
        $queue = new TaskQueue();
        $queue->setName('Editable Queue');
        $queue->setIsPublished(true);
        $this->em->persist($queue);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/task-queues/edit/'.$queue->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testFocusModeReturns200(): void
    {
        $queue = new TaskQueue();
        $queue->setName('Focus Queue');
        $queue->setIsPublished(true);
        $this->em->persist($queue);

        $task = new Task();
        $task->setTitle('Focus Task');
        $task->setIsPublished(true);
        $this->em->persist($task);

        $this->em->flush();

        $item = new TaskQueueItem();
        $item->setQueue($queue);
        $item->setTask($task);
        $item->setItemOrder(1);
        $this->em->persist($item);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/task-queues/focus/'.$queue->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Focus Task', $this->client->getResponse()->getContent());
    }

    public function testFocusModeEmptyQueue(): void
    {
        $queue = new TaskQueue();
        $queue->setName('Empty Queue');
        $queue->setIsPublished(true);
        $this->em->persist($queue);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/task-queues/focus/'.$queue->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('All tasks processed', $this->client->getResponse()->getContent());
    }

    public function testViewShowsQueueItemsSection(): void
    {
        $queue = new TaskQueue();
        $queue->setName('Queue with Items');
        $queue->setIsPublished(true);
        $this->em->persist($queue);

        $task = new Task();
        $task->setTitle('Linked Task');
        $task->setIsPublished(true);
        $this->em->persist($task);

        $this->em->flush();

        $item = new TaskQueueItem();
        $item->setQueue($queue);
        $item->setTask($task);
        $item->setItemOrder(1);
        $this->em->persist($item);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/task-queues/view/'.$queue->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Queue with Items', $content);
        $this->assertStringContainsString('Queue Items', $content);
    }
}
