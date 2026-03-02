<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DealStageChangeTest extends MauticMysqlTestCase
{
    public function testQuickStageChangeUpdatesStage(): void
    {
        $pipeline   = $this->createPipelineWithStages();
        $stages     = $pipeline->getStages()->toArray();
        $deal       = $this->createDeal($pipeline, $stages[0]);
        $dealId     = $deal->getId();
        $newStageId = $stages[1]->getId();

        $this->em->clear();

        $this->client->request(Request::METHOD_POST, '/s/mautomic/deals/'.$dealId.'/stage', [
            'stageId' => $newStageId,
        ]);

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->em->clear();
        $updatedDeal = $this->em->find(Deal::class, $dealId);
        $this->assertNotNull($updatedDeal);
        $this->assertSame($newStageId, $updatedDeal->getStage()->getId());
    }

    public function testQuickStageChangeInvalidStageRejected(): void
    {
        $pipeline1 = $this->createPipelineWithStages('Pipeline A');
        $pipeline2 = $this->createPipelineWithStages('Pipeline B');

        $deal = $this->createDeal($pipeline1, $pipeline1->getStages()->first());

        $wrongStage = $pipeline2->getStages()->first();

        $this->client->request(Request::METHOD_POST, '/s/mautomic/deals/'.$deal->getId().'/stage', [
            'stageId' => $wrongStage->getId(),
        ]);

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->em->clear();
        $unchangedDeal = $this->em->find(Deal::class, $deal->getId());
        $this->assertNotNull($unchangedDeal);
        $this->assertSame($pipeline1->getStages()->first()->getId(), $unchangedDeal->getStage()->getId());
    }

    public function testDealDetailShowsStageButtons(): void
    {
        $pipeline = $this->createPipelineWithStages();
        $deal     = $this->createDeal($pipeline, $pipeline->getStages()->first());

        $this->client->request(Request::METHOD_GET, '/s/mautomic/deals/view/'.$deal->getId());
        $content = $this->client->getResponse()->getContent();

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $stages = $pipeline->getStages()->toArray();
        foreach ($stages as $stage) {
            $this->assertStringContainsString($stage->getName(), $content);
        }
    }

    public function testWonStageShowsBadge(): void
    {
        $pipeline = $this->createPipelineWithStages();
        $stages   = $pipeline->getStages()->toArray();

        $wonStage = null;
        foreach ($stages as $stage) {
            if ('won' === $stage->getType()) {
                $wonStage = $stage;
                break;
            }
        }
        $this->assertNotNull($wonStage, 'Pipeline should have a won stage');

        $deal = $this->createDeal($pipeline, $wonStage);

        $this->client->request(Request::METHOD_GET, '/s/mautomic/deals/view/'.$deal->getId());
        $content = $this->client->getResponse()->getContent();

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('label-success', $content);
    }

    public function testLostStageShowsBadge(): void
    {
        $pipeline = $this->createPipelineWithStages();
        $stages   = $pipeline->getStages()->toArray();

        $lostStage = null;
        foreach ($stages as $stage) {
            if ('lost' === $stage->getType()) {
                $lostStage = $stage;
                break;
            }
        }
        $this->assertNotNull($lostStage, 'Pipeline should have a lost stage');

        $deal = $this->createDeal($pipeline, $lostStage);

        $this->client->request(Request::METHOD_GET, '/s/mautomic/deals/view/'.$deal->getId());
        $content = $this->client->getResponse()->getContent();

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('label-danger', $content);
    }

    public function testAuditLogRecordsStageChange(): void
    {
        $pipeline   = $this->createPipelineWithStages();
        $stages     = $pipeline->getStages()->toArray();
        $deal       = $this->createDeal($pipeline, $stages[0]);
        $dealId     = $deal->getId();
        $newStageId = $stages[1]->getId();

        $this->em->clear();

        $this->client->request(Request::METHOD_POST, '/s/mautomic/deals/'.$dealId.'/stage', [
            'stageId' => $newStageId,
        ]);

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $connection = $this->em->getConnection();
        $logs       = $connection->fetchAllAssociative(
            'SELECT * FROM '.MAUTIC_TABLE_PREFIX.'audit_log WHERE bundle = :bundle AND object = :object AND object_id = :objectId ORDER BY date_added DESC',
            [
                'bundle'   => 'mautomic_crm',
                'object'   => 'mautomic_crm.deal',
                'objectId' => $dealId,
            ]
        );

        $this->assertNotEmpty($logs, 'Audit log should contain an entry for the stage change');

        $found = false;
        foreach ($logs as $log) {
            $details = unserialize($log['details']);
            if (\is_array($details) && isset($details['stage'])) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Audit log should contain a stage change entry');
    }

    private function createPipelineWithStages(string $name = 'Sales Pipeline'): Pipeline
    {
        $pipeline = new Pipeline();
        $pipeline->setName($name);
        $pipeline->setIsPublished(true);
        $this->em->persist($pipeline);

        $stageData = [
            ['name' => 'Qualification', 'order' => 1, 'probability' => 25, 'type' => 'open'],
            ['name' => 'Proposal',      'order' => 2, 'probability' => 50, 'type' => 'open'],
            ['name' => 'Closed Won',    'order' => 3, 'probability' => 100, 'type' => 'won'],
            ['name' => 'Closed Lost',   'order' => 4, 'probability' => 0, 'type' => 'lost'],
        ];

        foreach ($stageData as $data) {
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

    private function createDeal(Pipeline $pipeline, Stage $stage): Deal
    {
        $deal = new Deal();
        $deal->setName('Test Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($stage);
        $deal->setIsPublished(true);
        $this->em->persist($deal);
        $this->em->flush();

        return $deal;
    }
}
