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

class TaskDealLinkingTest extends MauticMysqlTestCase
{
    public function testTaskLinkedToDealAppearsOnDealDetail(): void
    {
        $pipeline = $this->createPipelineWithStage();
        $stage    = $pipeline->getStages()->first();

        $deal = new Deal();
        $deal->setName('Widget Corp Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($stage);
        $deal->setIsPublished(true);
        $this->em->persist($deal);

        $task = new Task();
        $task->setTitle('Call decision maker');
        $task->setDeal($deal);
        $task->setStatus('open');
        $task->setPriority('high');
        $task->setIsPublished(true);
        $this->em->persist($task);

        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/deals/view/'.$deal->getId());
        $response = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('Call decision maker', $response->getContent());
        $this->assertStringContainsString('Tasks', $response->getContent());
    }

    public function testDealDetailShowsEmptyTasksSection(): void
    {
        $pipeline = $this->createPipelineWithStage();

        $deal = new Deal();
        $deal->setName('No Tasks Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($pipeline->getStages()->first());
        $deal->setIsPublished(true);
        $this->em->persist($deal);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/deals/view/'.$deal->getId());
        $response = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('No tasks linked to this deal yet', $response->getContent());
    }

    public function testOverdueTaskHighlightedOnDealDetail(): void
    {
        $pipeline = $this->createPipelineWithStage();

        $deal = new Deal();
        $deal->setName('Overdue Task Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($pipeline->getStages()->first());
        $deal->setIsPublished(true);
        $this->em->persist($deal);

        $overdueTask = new Task();
        $overdueTask->setTitle('Overdue follow up');
        $overdueTask->setDeal($deal);
        $overdueTask->setStatus('open');
        $overdueTask->setPriority('normal');
        $overdueTask->setDueDate(new \DateTime('-7 days'));
        $overdueTask->setIsPublished(true);
        $this->em->persist($overdueTask);

        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/deals/view/'.$deal->getId());
        $response = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('Overdue follow up', $response->getContent());
        $this->assertStringContainsString('text-danger', $response->getContent());
        $this->assertStringContainsString('label-danger', $response->getContent());
    }

    public function testTaskFormContainsDealAndContactFields(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/mautomic/tasks/new');

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertGreaterThan(0, $crawler->filter('#task_deal')->count(), 'Deal dropdown should exist');
        $this->assertGreaterThan(0, $crawler->filter('#task_contact')->count(), 'Contact dropdown should exist');
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
