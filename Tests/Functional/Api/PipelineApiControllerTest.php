<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Response;

class PipelineApiControllerTest extends MauticMysqlTestCase
{
    public function testPipelineCrudWorkflow(): void
    {
        $payload = [
            'name'        => 'Enterprise Sales',
            'description' => 'Pipeline for enterprise accounts',
            'isDefault'   => true,
            'isPublished' => true,
        ];

        $this->client->request('POST', '/api/mautomic/pipelines/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertArrayHasKey('pipeline', $response);

        $pipelineId = $response['pipeline']['id'];
        $this->assertGreaterThan(0, $pipelineId);
        $this->assertSame('Enterprise Sales', $response['pipeline']['name']);

        $editPayload = ['name' => 'Enterprise Sales (Renamed)'];
        $this->client->request('PATCH', "/api/mautomic/pipelines/{$pipelineId}/edit", $editPayload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertSame($pipelineId, $response['pipeline']['id']);
        $this->assertSame('Enterprise Sales (Renamed)', $response['pipeline']['name']);

        $this->client->request('GET', "/api/mautomic/pipelines/{$pipelineId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertSame($pipelineId, $response['pipeline']['id']);
        $this->assertSame('Enterprise Sales (Renamed)', $response['pipeline']['name']);

        $this->client->request('DELETE', "/api/mautomic/pipelines/{$pipelineId}/delete");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $this->client->request('GET', "/api/mautomic/pipelines/{$pipelineId}");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_NOT_FOUND, $clientResponse->getStatusCode());
    }

    public function testListPipelines(): void
    {
        $this->client->request('POST', '/api/mautomic/pipelines/new', [
            'name'        => 'Pipeline A',
            'isPublished' => true,
        ]);
        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $this->client->request('POST', '/api/mautomic/pipelines/new', [
            'name'        => 'Pipeline B',
            'isPublished' => true,
        ]);
        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/mautomic/pipelines');
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertArrayHasKey('mautomic_pipelines', $response);
        $this->assertGreaterThanOrEqual(2, count($response['mautomic_pipelines']));
    }
}
