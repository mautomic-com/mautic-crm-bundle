<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use Symfony\Component\HttpFoundation\Response;

class TaskApiControllerTest extends MauticMysqlTestCase
{
    private int $dealId;

    protected function setUp(): void
    {
        parent::setUp();

        $pipeline = new Pipeline();
        $pipeline->setName('API Test Pipeline');
        $pipeline->setIsPublished(true);

        $stage = new Stage();
        $stage->setName('Discovery');
        $stage->setOrder(1);
        $stage->setProbability(10);
        $stage->setType('open');
        $stage->setPipeline($pipeline);
        $pipeline->addStage($stage);

        $this->em->persist($pipeline);
        $this->em->persist($stage);

        $deal = new Deal();
        $deal->setName('API Test Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($stage);
        $deal->setIsPublished(true);
        $this->em->persist($deal);

        $this->em->flush();

        $this->dealId = $deal->getId();
    }

    public function testCreateTaskWithDeal(): void
    {
        $payload = [
            'title'       => 'Follow up call',
            'deal'        => $this->dealId,
            'status'      => 'open',
            'priority'    => 'high',
            'isPublished' => true,
        ];

        $this->client->request('POST', '/api/mautomic/tasks/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertArrayHasKey('task', $response);
        $this->assertSame('Follow up call', $response['task']['title']);
        $this->assertSame($this->dealId, $response['task']['deal']['id']);
    }

    public function testCreateTaskWithoutDeal(): void
    {
        $payload = [
            'title'       => 'Standalone task',
            'status'      => 'open',
            'priority'    => 'normal',
            'isPublished' => true,
        ];

        $this->client->request('POST', '/api/mautomic/tasks/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertArrayHasKey('task', $response);
        $this->assertSame('Standalone task', $response['task']['title']);
    }

    public function testTaskCrudWorkflow(): void
    {
        $this->client->request('POST', '/api/mautomic/tasks/new', [
            'title'       => 'CRUD Test Task',
            'deal'        => $this->dealId,
            'status'      => 'open',
            'priority'    => 'low',
            'isPublished' => true,
        ]);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $taskId = $response['task']['id'];

        $this->client->request('PATCH', "/api/mautomic/tasks/{$taskId}/edit", [
            'title'  => 'Updated CRUD Task',
            'status' => 'completed',
        ]);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertSame('Updated CRUD Task', $response['task']['title']);

        $this->client->request('GET', "/api/mautomic/tasks/{$taskId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertSame('Updated CRUD Task', $response['task']['title']);
        $this->assertSame('completed', $response['task']['status']);

        $this->client->request('DELETE', "/api/mautomic/tasks/{$taskId}/delete");
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testListTasks(): void
    {
        $this->client->request('POST', '/api/mautomic/tasks/new', [
            'title'       => 'Task Alpha',
            'status'      => 'open',
            'priority'    => 'normal',
            'isPublished' => true,
        ]);
        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $this->client->request('POST', '/api/mautomic/tasks/new', [
            'title'       => 'Task Beta',
            'deal'        => $this->dealId,
            'status'      => 'open',
            'priority'    => 'high',
            'isPublished' => true,
        ]);
        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $this->client->request('GET', '/api/mautomic/tasks');
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertArrayHasKey('mautomic_tasks', $response);
        $this->assertGreaterThanOrEqual(2, count($response['mautomic_tasks']));
    }
}
