<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MautomicCrmBundle\Entity\Task;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TaskControllerTest extends MauticMysqlTestCase
{
    public function testIndexActionReturns200(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/mautomic/tasks');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testIndexShowsTaskInList(): void
    {
        $task = new Task();
        $task->setTitle('Call the client');
        $task->setPriority('high');
        $task->setStatus('open');
        $task->setIsPublished(true);
        $this->em->persist($task);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/tasks');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Call the client', $this->client->getResponse()->getContent());
    }

    public function testNewActionReturns200(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/mautomic/tasks/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testNewActionContainsForm(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/mautomic/tasks/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertGreaterThan(0, $crawler->filter('form[name="task"]')->count(), 'Task form should exist');
    }

    public function testEditActionReturns200(): void
    {
        $task = new Task();
        $task->setTitle('Edit Me');
        $task->setIsPublished(true);
        $this->em->persist($task);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/tasks/edit/'.$task->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testViewActionReturns200(): void
    {
        $task = new Task();
        $task->setTitle('View This Task');
        $task->setIsPublished(true);
        $this->em->persist($task);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/tasks/view/'.$task->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('View This Task', $this->client->getResponse()->getContent());
    }
}
