<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\EventListener;

use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\DealRepository;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\StageRepository;
use MauticPlugin\MautomicCrmBundle\Event\DealEvent;
use MauticPlugin\MautomicCrmBundle\EventListener\CampaignSubscriber;
use MauticPlugin\MautomicCrmBundle\Model\DealModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CampaignSubscriberTest extends TestCase
{
    private RealTimeExecutioner&MockObject $realTimeExecutioner;

    private ContactTracker&MockObject $contactTracker;

    private DealModel&MockObject $dealModel;

    private DealRepository&MockObject $dealRepository;

    private StageRepository&MockObject $stageRepository;

    private CampaignSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->realTimeExecutioner = $this->createMock(RealTimeExecutioner::class);
        $this->contactTracker      = $this->createMock(ContactTracker::class);
        $this->dealModel           = $this->createMock(DealModel::class);
        $this->dealRepository      = $this->createMock(DealRepository::class);
        $this->stageRepository     = $this->createMock(StageRepository::class);

        /** @var ModelFactory<DealModel>&MockObject $modelFactory */
        $modelFactory = $this->createMock(ModelFactory::class);
        $modelFactory->method('getModel')->with('mautomic_crm.deal')->willReturn($this->dealModel);

        $this->subscriber = new CampaignSubscriber(
            $this->realTimeExecutioner,
            $this->contactTracker,
            $modelFactory,
            $this->dealRepository,
            $this->stageRepository,
        );
    }

    public function testOnCampaignBuildRegistersDecisionAndAction(): void
    {
        $event = $this->createMock(CampaignBuilderEvent::class);

        $event->expects($this->once())
            ->method('addDecision')
            ->with('deal.stage_changed', $this->isType('array'));

        $event->expects($this->once())
            ->method('addAction')
            ->with('deal.update_stage', $this->isType('array'));

        $this->subscriber->onCampaignBuild($event);
    }

    public function testOnDealStageChangedSkipsDealsWithoutContact(): void
    {
        $deal = new Deal();
        $deal->setName('No Contact Deal');

        $event = new DealEvent($deal);

        $this->realTimeExecutioner->expects($this->never())->method('execute');
        $this->contactTracker->expects($this->never())->method('setSystemContact');

        $this->subscriber->onDealStageChanged($event);
    }

    public function testOnDealStageChangedCallsRealTimeExecutioner(): void
    {
        $contact = $this->createMock(Lead::class);

        $deal = new Deal();
        $deal->setName('Test Deal');
        $deal->setContact($contact);

        $event = new DealEvent($deal);

        $this->contactTracker->expects($this->exactly(2))
            ->method('setSystemContact')
            ->willReturnCallback(function (?Lead $lead) use ($contact): void {
                static $callCount = 0;
                ++$callCount;
                if (1 === $callCount) {
                    $this->assertSame($contact, $lead);
                } else {
                    $this->assertNull($lead);
                }
            });

        $this->realTimeExecutioner->expects($this->once())
            ->method('execute')
            ->with('deal.stage_changed', $event, 'mautomic_crm.deal', $this->anything());

        $this->subscriber->onDealStageChanged($event);
    }

    public function testOnCampaignTriggerDecisionMatchesCriteria(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getId')->willReturn(1);

        $deal = new Deal();
        $deal->setName('Match Deal');
        $deal->setPipeline($pipeline);

        $dealEvent = new DealEvent($deal);
        $dealEvent->setPreviousStageId(10);
        $dealEvent->setNewStageId(20);

        $campaignEvent = $this->createCampaignExecutionEvent(
            'deal.stage_changed',
            $dealEvent,
            ['pipeline' => 1, 'from_stage' => 10, 'to_stage' => 20],
        );

        $campaignEvent->expects($this->once())
            ->method('setResult')
            ->with(true);

        $this->subscriber->onCampaignTriggerDecision($campaignEvent);
    }

    public function testOnCampaignTriggerDecisionReturnsFalseOnMismatch(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getId')->willReturn(1);

        $deal = new Deal();
        $deal->setName('Mismatch Deal');
        $deal->setPipeline($pipeline);

        $dealEvent = new DealEvent($deal);
        $dealEvent->setPreviousStageId(10);
        $dealEvent->setNewStageId(20);

        $campaignEvent = $this->createCampaignExecutionEvent(
            'deal.stage_changed',
            $dealEvent,
            ['pipeline' => 999, 'from_stage' => null, 'to_stage' => null],
        );

        $campaignEvent->expects($this->once())
            ->method('setResult')
            ->with(false);

        $this->subscriber->onCampaignTriggerDecision($campaignEvent);
    }

    public function testOnCampaignTriggerDecisionIgnoresWrongContext(): void
    {
        /** @phpstan-ignore classConstant.deprecatedClass */
        $campaignEvent = $this->createMock(CampaignExecutionEvent::class);
        $campaignEvent->method('checkContext')->with('deal.stage_changed')->willReturn(false);

        $campaignEvent->expects($this->never())->method('setResult');

        $this->subscriber->onCampaignTriggerDecision($campaignEvent);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @phpstan-ignore return.deprecatedClass, classConstant.deprecatedClass
     */
    private function createCampaignExecutionEvent(
        string $context,
        DealEvent $dealEvent,
        array $config,
    ): CampaignExecutionEvent&MockObject {
        /** @phpstan-ignore classConstant.deprecatedClass */
        $event = $this->createMock(CampaignExecutionEvent::class);
        $event->method('checkContext')->with($context)->willReturn(true);
        $event->method('getEventDetails')->willReturn($dealEvent);
        $event->method('getConfig')->willReturn($config);

        return $event;
    }
}
