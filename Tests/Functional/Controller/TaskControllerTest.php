<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
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

    public function testNewFormHasDealAndContactFields(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/mautomic/tasks/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertGreaterThan(0, $crawler->filter('select[id="task_deal"]')->count(), 'Deal select should exist');
        $this->assertGreaterThan(0, $crawler->filter('select[id="task_contact"]')->count(), 'Contact select should exist');
    }

    public function testCreateTaskLinkedToDeal(): void
    {
        $pipeline = $this->createPipelineWithStage();
        $stage    = $pipeline->getStages()->first();

        $deal = new Deal();
        $deal->setName('Big Corp Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($stage);
        $deal->setIsPublished(true);
        $this->em->persist($deal);

        $task = new Task();
        $task->setTitle('Follow up call');
        $task->setStatus('open');
        $task->setPriority('high');
        $task->setDeal($deal);
        $task->setIsPublished(true);
        $this->em->persist($task);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/deals/view/'.$deal->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Follow up call', $this->client->getResponse()->getContent());
    }

    public function testOverdueTaskHighlightedOnDealDetail(): void
    {
        $pipeline = $this->createPipelineWithStage();
        $stage    = $pipeline->getStages()->first();

        $deal = new Deal();
        $deal->setName('Overdue Test Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($stage);
        $deal->setIsPublished(true);
        $this->em->persist($deal);

        $task = new Task();
        $task->setTitle('Overdue task');
        $task->setStatus('open');
        $task->setPriority('normal');
        $task->setDueDate(new \DateTime('-7 days'));
        $task->setDeal($deal);
        $task->setIsPublished(true);
        $this->em->persist($task);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/mautomic/deals/view/'.$deal->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertGreaterThan(0, $crawler->filter('tr.text-danger')->count(), 'Overdue task row should have text-danger class');
    }

    private function createPipelineWithStage(): Pipeline
    {
        $pipeline = new Pipeline();
        $pipeline->setName('Test Pipeline');
        $pipeline->setIsPublished(true);
        $this->em->persist($pipeline);

        $stage = new Stage();
        $stage->setName('Qualification');
        $stage->setPipeline($pipeline);
        $stage->setOrder(1);
        $stage->setProbability(25);
        $stage->setType('open');
        $this->em->persist($stage);

        $pipeline->addStage($stage);
        $this->em->flush();

        return $pipeline;
    }
}
