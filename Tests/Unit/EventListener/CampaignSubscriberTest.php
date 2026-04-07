<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\PipelineRepository;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use MauticPlugin\MautomicCrmBundle\Entity\StageRepository;
use MauticPlugin\MautomicCrmBundle\EventListener\CampaignSubscriber;
use MauticPlugin\MautomicCrmBundle\Model\DealModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CampaignSubscriberTest extends TestCase
{
    private DealModel&MockObject $dealModel;

    private PipelineRepository&MockObject $pipelineRepository;

    private StageRepository&MockObject $stageRepository;

    private CampaignSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->dealModel          = $this->createMock(DealModel::class);
        $this->pipelineRepository = $this->createMock(PipelineRepository::class);
        $this->stageRepository    = $this->createMock(StageRepository::class);

        /** @var ModelFactory<DealModel>&MockObject $modelFactory */
        $modelFactory = $this->createMock(ModelFactory::class);
        $modelFactory->method('getModel')->with('mautomic_crm.deal')->willReturn($this->dealModel);

        $this->subscriber = new CampaignSubscriber(
            $modelFactory,
            $this->pipelineRepository,
            $this->stageRepository,
        );
    }

    public function testOnCampaignBuildRegistersCreateDealAction(): void
    {
        $event = $this->createMock(CampaignBuilderEvent::class);

        $event->expects($this->never())->method('addDecision');

        $event->expects($this->once())
            ->method('addAction')
            ->with('deal.create', $this->isType('array'));

        $this->subscriber->onCampaignBuild($event);
    }

    public function testOnCampaignCreateDealIgnoresWrongContext(): void
    {
        $pendingEvent = $this->createPendingEvent(
            'wrong.context',
            ['name' => 'Test', 'pipeline' => 1],
        );

        $this->dealModel->expects($this->never())->method('saveEntity');

        $this->subscriber->onCampaignCreateDeal($pendingEvent);
    }

    public function testOnCampaignCreateDealFailsWhenPipelineNotFound(): void
    {
        $this->pipelineRepository->method('find')->with(999)->willReturn(null);

        $pendingEvent = $this->createPendingEvent(
            'deal.create',
            ['name' => 'Test', 'pipeline' => 999],
        );

        $pendingEvent->expects($this->once())
            ->method('failAll')
            ->with('Pipeline not found.');

        $this->subscriber->onCampaignCreateDeal($pendingEvent);
    }

    public function testOnCampaignCreateDealFailsWhenNoStageAvailable(): void
    {
        $pipeline = new Pipeline();
        $pipeline->setName('Empty Pipeline');

        $this->pipelineRepository->method('find')->with(1)->willReturn($pipeline);
        $this->stageRepository->method('find')->willReturn(null);

        $pendingEvent = $this->createPendingEvent(
            'deal.create',
            ['name' => 'Test', 'pipeline' => 1, 'stage' => 999],
        );

        $pendingEvent->expects($this->once())
            ->method('failAll')
            ->with('No stage available for the selected pipeline.');

        $this->subscriber->onCampaignCreateDeal($pendingEvent);
    }

    public function testOnCampaignCreateDealCreatesDealsForContacts(): void
    {
        $stage = $this->createMock(Stage::class);

        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getStages')->willReturn(new ArrayCollection([$stage]));

        $this->pipelineRepository->method('find')->with(1)->willReturn($pipeline);

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')->willReturn(10);
        $contact2 = $this->createMock(Lead::class);
        $contact2->method('getId')->willReturn(20);

        $log1 = $this->createMock(LeadEventLog::class);
        $log2 = $this->createMock(LeadEventLog::class);

        $savedDeals = [];
        $this->dealModel->expects($this->exactly(2))
            ->method('saveEntity')
            ->willReturnCallback(function (Deal $deal) use (&$savedDeals): void {
                $savedDeals[] = $deal;
            });

        $pendingEvent = $this->createPendingEventWithContacts(
            'deal.create',
            [
                'name'        => 'Campaign Deal',
                'pipeline'    => 1,
                'description' => 'Auto-created',
                'amount'      => '500.00',
                'currency'    => 'USD',
            ],
            [100 => $contact1, 200 => $contact2],
            [100 => $log1, 200 => $log2],
        );

        $pendingEvent->expects($this->exactly(2))->method('pass');

        $this->subscriber->onCampaignCreateDeal($pendingEvent);

        $this->assertCount(2, $savedDeals);
        $this->assertSame('Campaign Deal', $savedDeals[0]->getName());
        $this->assertSame('Auto-created', $savedDeals[0]->getDescription());
        $this->assertSame('500.00', $savedDeals[0]->getAmount());
        $this->assertSame('USD', $savedDeals[0]->getCurrency());
        $this->assertSame($contact1, $savedDeals[0]->getContact());
        $this->assertSame($pipeline, $savedDeals[0]->getPipeline());
    }

    public function testOnCampaignCreateDealUsesFirstStageWhenNoneSpecified(): void
    {
        $stage1 = $this->createMock(Stage::class);
        $stage2 = $this->createMock(Stage::class);

        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getStages')->willReturn(new ArrayCollection([$stage1, $stage2]));

        $this->pipelineRepository->method('find')->with(1)->willReturn($pipeline);
        $this->stageRepository->expects($this->never())->method('find');

        $contact = $this->createMock(Lead::class);
        $log     = $this->createMock(LeadEventLog::class);

        $savedDeal = null;
        $this->dealModel->expects($this->once())
            ->method('saveEntity')
            ->willReturnCallback(function (Deal $deal) use (&$savedDeal): void {
                $savedDeal = $deal;
            });

        $pendingEvent = $this->createPendingEventWithContacts(
            'deal.create',
            ['name' => 'Test', 'pipeline' => 1],
            [100    => $contact],
            [100    => $log],
        );

        $pendingEvent->expects($this->once())->method('pass');

        $this->subscriber->onCampaignCreateDeal($pendingEvent);

        $this->assertSame($stage1, $savedDeal->getStage());
    }

    public function testOnCampaignCreateDealUsesExplicitStage(): void
    {
        $stage = $this->createMock(Stage::class);

        $pipeline = $this->createMock(Pipeline::class);

        $this->pipelineRepository->method('find')->with(1)->willReturn($pipeline);
        $this->stageRepository->method('find')->with(5)->willReturn($stage);

        $contact = $this->createMock(Lead::class);
        $log     = $this->createMock(LeadEventLog::class);

        $savedDeal = null;
        $this->dealModel->expects($this->once())
            ->method('saveEntity')
            ->willReturnCallback(function (Deal $deal) use (&$savedDeal): void {
                $savedDeal = $deal;
            });

        $pendingEvent = $this->createPendingEventWithContacts(
            'deal.create',
            ['name' => 'Test', 'pipeline' => 1, 'stage' => 5],
            [100    => $contact],
            [100    => $log],
        );

        $pendingEvent->expects($this->once())->method('pass');

        $this->subscriber->onCampaignCreateDeal($pendingEvent);

        $this->assertSame($stage, $savedDeal->getStage());
    }

    public function testOnCampaignCreateDealDefaultsNameWhenEmpty(): void
    {
        $stage = $this->createMock(Stage::class);

        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getStages')->willReturn(new ArrayCollection([$stage]));

        $this->pipelineRepository->method('find')->with(1)->willReturn($pipeline);

        $contact = $this->createMock(Lead::class);
        $log     = $this->createMock(LeadEventLog::class);

        $savedDeal = null;
        $this->dealModel->expects($this->once())
            ->method('saveEntity')
            ->willReturnCallback(function (Deal $deal) use (&$savedDeal): void {
                $savedDeal = $deal;
            });

        $pendingEvent = $this->createPendingEventWithContacts(
            'deal.create',
            ['name' => '', 'pipeline' => 1],
            [100    => $contact],
            [100    => $log],
        );

        $pendingEvent->expects($this->once())->method('pass');

        $this->subscriber->onCampaignCreateDeal($pendingEvent);

        $this->assertSame('Campaign Deal', $savedDeal->getName());
    }

    public function testOnCampaignCreateDealReplacesContactTokensInName(): void
    {
        $stage = $this->createMock(Stage::class);

        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getStages')->willReturn(new ArrayCollection([$stage]));

        $this->pipelineRepository->method('find')->with(1)->willReturn($pipeline);

        $contact = $this->createMock(Lead::class);
        $contact->method('getFirstname')->willReturn('John');
        $contact->method('getLastname')->willReturn('Doe');
        $contact->method('getEmail')->willReturn('john@example.com');
        $log = $this->createMock(LeadEventLog::class);

        $savedDeal = null;
        $this->dealModel->expects($this->once())
            ->method('saveEntity')
            ->willReturnCallback(function (Deal $deal) use (&$savedDeal): void {
                $savedDeal = $deal;
            });

        $pendingEvent = $this->createPendingEventWithContacts(
            'deal.create',
            ['name' => 'Deal - {contactfield=firstname} {contactfield=lastname}', 'pipeline' => 1],
            [100    => $contact],
            [100    => $log],
        );

        $pendingEvent->expects($this->once())->method('pass');

        $this->subscriber->onCampaignCreateDeal($pendingEvent);

        $this->assertSame('Deal - John Doe', $savedDeal->getName());
    }

    public function testTokenReplacementHandlesUnknownFieldGracefully(): void
    {
        $stage = $this->createMock(Stage::class);

        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getStages')->willReturn(new ArrayCollection([$stage]));

        $this->pipelineRepository->method('find')->with(1)->willReturn($pipeline);

        $contact = $this->createMock(Lead::class);
        $contact->method('getFieldValue')->with('custom_field')->willReturn(null);
        $log = $this->createMock(LeadEventLog::class);

        $savedDeal = null;
        $this->dealModel->expects($this->once())
            ->method('saveEntity')
            ->willReturnCallback(function (Deal $deal) use (&$savedDeal): void {
                $savedDeal = $deal;
            });

        $pendingEvent = $this->createPendingEventWithContacts(
            'deal.create',
            ['name' => 'Deal for {contactfield=custom_field}', 'pipeline' => 1],
            [100    => $contact],
            [100    => $log],
        );

        $pendingEvent->expects($this->once())->method('pass');

        $this->subscriber->onCampaignCreateDeal($pendingEvent);

        $this->assertSame('Deal for ', $savedDeal->getName());
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createPendingEvent(string $context, array $config): PendingEvent&MockObject
    {
        $campaignEvent = $this->createMock(Event::class);
        $campaignEvent->method('getProperties')->willReturn($config);

        $pendingEvent = $this->createMock(PendingEvent::class);
        $pendingEvent->method('checkContext')->willReturnCallback(
            fn (string $ctx): bool => $ctx === $context,
        );
        $pendingEvent->method('getEvent')->willReturn($campaignEvent);

        return $pendingEvent;
    }

    /**
     * @param array<string, mixed>     $config
     * @param array<int, Lead>         $contacts
     * @param array<int, LeadEventLog> $logs
     */
    private function createPendingEventWithContacts(
        string $context,
        array $config,
        array $contacts,
        array $logs,
    ): PendingEvent&MockObject {
        $pendingEvent = $this->createPendingEvent($context, $config);
        $pendingEvent->method('getContacts')->willReturn(new ArrayCollection($contacts));
        $pendingEvent->method('getPending')->willReturn(new ArrayCollection($logs));

        return $pendingEvent;
    }
}
