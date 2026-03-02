<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use Symfony\Component\HttpFoundation\Response;

class TaskApiControllerTest extends MauticMysqlTestCase
{
    private int $dealId;
    private int $contactId;

    protected function setUp(): void
    {
        parent::setUp();

        $pipeline = new Pipeline();
        $pipeline->setName('API Test Pipeline');
        $pipeline->setIsPublished(true);

        $stage = new Stage();
        $stage->setName('Prospecting');
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

        $contact = new Lead();
        $contact->setFirstname('John');
        $contact->setLastname('Doe');
        $contact->setEmail('john.doe@example.com');
        $this->em->persist($contact);

        $this->em->flush();

        $this->dealId    = $deal->getId();
        $this->contactId = $contact->getId();
    }

    public function testCreateTaskWithDealViaApi(): void
    {
        $payload = [
            'title'       => 'Follow up with client',
            'status'      => 'open',
            'priority'    => 'high',
            'deal'        => $this->dealId,
            'isPublished' => true,
        ];

        $this->client->request('POST', '/api/mautomic/tasks/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertArrayHasKey('task', $response);
        $this->assertSame('Follow up with client', $response['task']['title']);
        $this->assertSame($this->dealId, $response['task']['deal']['id']);
    }

    public function testCreateTaskWithContactViaApi(): void
    {
        $payload = [
            'title'       => 'Call contact',
            'status'      => 'open',
            'priority'    => 'normal',
            'contact'     => $this->contactId,
            'isPublished' => true,
        ];

        $this->client->request('POST', '/api/mautomic/tasks/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertArrayHasKey('task', $response);
        $this->assertSame('Call contact', $response['task']['title']);
        $this->assertSame($this->contactId, $response['task']['contact']['id']);
    }

    public function testGetTaskIncludesDealAndContact(): void
    {
        $payload = [
            'title'       => 'Full linking test',
            'status'      => 'open',
            'priority'    => 'normal',
            'deal'        => $this->dealId,
            'contact'     => $this->contactId,
            'isPublished' => true,
        ];

        $this->client->request('POST', '/api/mautomic/tasks/new', $payload);
        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $taskId         = $createResponse['task']['id'];

        $this->client->request('GET', "/api/mautomic/tasks/{$taskId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertSame($taskId, $response['task']['id']);
        $this->assertSame($this->dealId, $response['task']['deal']['id']);
        $this->assertSame($this->contactId, $response['task']['contact']['id']);
    }
}
