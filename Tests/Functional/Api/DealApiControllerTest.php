<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use Symfony\Component\HttpFoundation\Response;

class DealApiControllerTest extends MauticMysqlTestCase
{
    private int $pipelineId;
    private int $stageId;

    protected function setUp(): void
    {
        parent::setUp();

        $pipeline = new Pipeline();
        $pipeline->setName('Test Pipeline');
        $pipeline->setIsPublished(true);

        $stage = new Stage();
        $stage->setName('Qualification');
        $stage->setOrder(1);
        $stage->setProbability(25);
        $stage->setType('open');
        $stage->setPipeline($pipeline);
        $pipeline->addStage($stage);

        $this->em->persist($pipeline);
        $this->em->persist($stage);
        $this->em->flush();

        $this->pipelineId = $pipeline->getId();
        $this->stageId    = $stage->getId();
    }

    public function testDealCrudWorkflow(): void
    {
        $payload = [
            'name'        => 'Acme Corp Deal',
            'amount'      => '50000.00',
            'currency'    => 'USD',
            'pipeline'    => $this->pipelineId,
            'stage'       => $this->stageId,
            'isPublished' => true,
        ];

        $this->client->request('POST', '/api/mautomic/deals/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertArrayHasKey('deal', $response);

        $dealId = $response['deal']['id'];
        $this->assertGreaterThan(0, $dealId);
        $this->assertSame('Acme Corp Deal', $response['deal']['name']);

        $editPayload = ['name' => 'Acme Corp Deal (Updated)', 'amount' => '75000.00'];
        $this->client->request('PATCH', "/api/mautomic/deals/{$dealId}/edit", $editPayload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertSame($dealId, $response['deal']['id']);
        $this->assertSame('Acme Corp Deal (Updated)', $response['deal']['name']);

        $this->client->request('GET', "/api/mautomic/deals/{$dealId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertSame($dealId, $response['deal']['id']);
        $this->assertSame('Acme Corp Deal (Updated)', $response['deal']['name']);

        $this->client->request('DELETE', "/api/mautomic/deals/{$dealId}/delete");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $this->client->request('GET', "/api/mautomic/deals/{$dealId}");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_NOT_FOUND, $clientResponse->getStatusCode());
    }

    public function testListDeals(): void
    {
        $this->client->request('POST', '/api/mautomic/deals/new', [
            'name'        => 'Deal Alpha',
            'pipeline'    => $this->pipelineId,
            'stage'       => $this->stageId,
            'isPublished' => true,
        ]);
        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $this->client->request('POST', '/api/mautomic/deals/new', [
            'name'        => 'Deal Beta',
            'pipeline'    => $this->pipelineId,
            'stage'       => $this->stageId,
            'isPublished' => true,
        ]);
        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $this->client->request('GET', '/api/mautomic/deals');
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertArrayHasKey('mautomic_deals', $response);
        $this->assertGreaterThanOrEqual(2, count($response['mautomic_deals']));
    }
}
