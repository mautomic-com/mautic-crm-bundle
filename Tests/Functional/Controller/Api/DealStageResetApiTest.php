<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use Symfony\Component\HttpFoundation\Response;

class DealStageResetApiTest extends MauticMysqlTestCase
{
    public function testPipelineChangeResetsStageThroughApi(): void
    {
        $pipeline1 = $this->createPipelineWithStages('Pipeline One', [
            ['name' => 'P1 Stage 1', 'order' => 1, 'probability' => 25, 'type' => 'open'],
            ['name' => 'P1 Stage 2', 'order' => 2, 'probability' => 50, 'type' => 'open'],
        ]);

        $pipeline2 = $this->createPipelineWithStages('Pipeline Two', [
            ['name' => 'P2 First',  'order' => 1, 'probability' => 10, 'type' => 'open'],
            ['name' => 'P2 Second', 'order' => 2, 'probability' => 50, 'type' => 'open'],
        ]);

        $p1Stages = $pipeline1->getStages()->toArray();
        $p2Stages = $pipeline2->getStages()->toArray();

        // Create deal in pipeline 1 at stage 2
        $this->client->request('POST', '/api/mautomic/deals/new', [
            'name'        => 'Reset Test Deal',
            'pipeline'    => $pipeline1->getId(),
            'stage'       => $p1Stages[1]->getId(),
            'isPublished' => true,
        ]);
        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $dealId = $createResponse['deal']['id'];

        // PATCH to change pipeline
        $this->client->request('PATCH', "/api/mautomic/deals/{$dealId}/edit", [
            'pipeline' => $pipeline2->getId(),
        ]);

        $patchResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        // Verify stage was reset to first of pipeline 2
        $this->assertSame($p2Stages[0]->getId(), $patchResponse['deal']['stage']['id']);
    }

    /**
     * @param array<int, array{name: string, order: int, probability: int, type: string}> $stagesData
     */
    private function createPipelineWithStages(string $name, array $stagesData): Pipeline
    {
        $pipeline = new Pipeline();
        $pipeline->setName($name);
        $pipeline->setIsPublished(true);
        $this->em->persist($pipeline);

        foreach ($stagesData as $data) {
            $stage = new Stage();
            $stage->setName($data['name']);
            $stage->setPipeline($pipeline);
            $stage->setOrder($data['order']);
            $stage->setProbability($data['probability']);
            $stage->setType($data['type']);
            $this->em->persist($stage);
            $pipeline->addStage($stage);
        }

        $this->em->flush();

        return $pipeline;
    }
}
