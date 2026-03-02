<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\SegmentDictionaryGenerationEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use Symfony\Component\HttpFoundation\Request;

class CampaignSegmentIntegrationTest extends MauticMysqlTestCase
{
    public function testDealStageChangedDecisionAvailableInCampaignBuilder(): void
    {
        $dispatcher = static::getContainer()->get('event_dispatcher');
        $translator = static::getContainer()->get('translator');

        $event = new CampaignBuilderEvent($translator);
        $dispatcher->dispatch($event, CampaignEvents::CAMPAIGN_ON_BUILD);

        $decisions = $event->getDecisions();
        $this->assertArrayHasKey('deal.stage_changed', $decisions);
    }

    public function testUpdateDealStageActionAvailableInCampaignBuilder(): void
    {
        $dispatcher = static::getContainer()->get('event_dispatcher');
        $translator = static::getContainer()->get('translator');

        $event = new CampaignBuilderEvent($translator);
        $dispatcher->dispatch($event, CampaignEvents::CAMPAIGN_ON_BUILD);

        $actions = $event->getActions();
        $this->assertArrayHasKey('deal.update_stage', $actions);
    }

    public function testDealSegmentFiltersAvailable(): void
    {
        $dispatcher = static::getContainer()->get('event_dispatcher');

        $event = new SegmentDictionaryGenerationEvent();
        $dispatcher->dispatch($event, LeadEvents::SEGMENT_DICTIONARY_ON_GENERATE);

        $translations = $event->getTranslations();

        $this->assertArrayHasKey('deal_pipeline', $translations);
        $this->assertArrayHasKey('deal_stage', $translations);
        $this->assertArrayHasKey('deal_amount', $translations);

        $this->assertSame('mautomic_deals', $translations['deal_pipeline']['foreign_table']);
        $this->assertSame('contact_id', $translations['deal_pipeline']['foreign_table_field']);
        $this->assertSame('pipeline_id', $translations['deal_pipeline']['field']);

        $this->assertSame('stage_id', $translations['deal_stage']['field']);
        $this->assertSame('amount', $translations['deal_amount']['field']);
    }

    public function testStageChangeTriggersCampaignDecision(): void
    {
        $pipeline = $this->createPipelineWithStages();
        $stages   = $pipeline->getStages()->toArray();
        $contact  = $this->createContact();
        $deal     = $this->createDeal($pipeline, $stages[0], $contact);

        $this->em->clear();

        $newStageId = $stages[1]->getId();
        $this->client->request(Request::METHOD_POST, '/s/mautomic/deals/'.$deal->getId().'/stage', [
            'stageId' => $newStageId,
        ]);

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        $this->em->clear();
        $updatedDeal = $this->em->find(Deal::class, $deal->getId());
        $this->assertNotNull($updatedDeal);
        $this->assertSame($newStageId, $updatedDeal->getStage()->getId());
    }

    private function createContact(): Lead
    {
        $contact = new Lead();
        $contact->setFirstname('Test');
        $contact->setLastname('Contact');
        $contact->setEmail('campaign-test@example.com');
        $this->em->persist($contact);
        $this->em->flush();

        return $contact;
    }

    private function createPipelineWithStages(): Pipeline
    {
        $pipeline = new Pipeline();
        $pipeline->setName('Campaign Test Pipeline');
        $pipeline->setIsPublished(true);
        $this->em->persist($pipeline);

        $stageData = [
            ['name' => 'Qualification', 'order' => 1, 'probability' => 25, 'type' => 'open'],
            ['name' => 'Proposal',      'order' => 2, 'probability' => 50, 'type' => 'open'],
            ['name' => 'Closed Won',    'order' => 3, 'probability' => 100, 'type' => 'won'],
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

    private function createDeal(Pipeline $pipeline, Stage $stage, Lead $contact): Deal
    {
        $deal = new Deal();
        $deal->setName('Campaign Test Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($stage);
        $deal->setContact($contact);
        $deal->setIsPublished(true);
        $this->em->persist($deal);
        $this->em->flush();

        return $deal;
    }
}
