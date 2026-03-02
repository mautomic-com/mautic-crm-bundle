<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MautomicCrmBundle\Entity\DealField;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use Symfony\Component\HttpFoundation\Response;

class DealApiCustomFieldsTest extends MauticMysqlTestCase
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

    public function testCreateDealWithCustomFields(): void
    {
        $field = new DealField();
        $field->setLabel('Contract Number');
        $field->setAlias('contract_number');
        $field->setType('text');
        $field->setIsPublished(true);
        $this->em->persist($field);
        $this->em->flush();

        $this->client->request('POST', '/api/mautomic/deals/new', [
            'name'         => 'API Custom Deal',
            'pipeline'     => $this->pipelineId,
            'stage'        => $this->stageId,
            'isPublished'  => true,
            'customFields' => [
                'contract_number' => 'C-API-001',
            ],
        ]);

        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertArrayHasKey('deal', $response);
        $this->assertArrayHasKey('customFieldValues', $response['deal']);
        $this->assertSame('C-API-001', $response['deal']['customFieldValues']['contract_number']);
    }

    public function testGetDealIncludesCustomFields(): void
    {
        $field = new DealField();
        $field->setLabel('Source');
        $field->setAlias('source');
        $field->setType('text');
        $field->setIsPublished(true);
        $this->em->persist($field);
        $this->em->flush();

        $this->client->request('POST', '/api/mautomic/deals/new', [
            'name'         => 'API Get Deal',
            'pipeline'     => $this->pipelineId,
            'stage'        => $this->stageId,
            'isPublished'  => true,
            'customFields' => [
                'source' => 'Referral',
            ],
        ]);

        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $dealId         = $createResponse['deal']['id'];

        $this->client->request('GET', "/api/mautomic/deals/{$dealId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertArrayHasKey('customFieldValues', $response['deal']);
        $this->assertSame('Referral', $response['deal']['customFieldValues']['source']);
    }
}
