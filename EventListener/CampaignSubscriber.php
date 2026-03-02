<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\LeadBundle\Tracker\ContactTracker;
use MauticPlugin\MautomicCrmBundle\Entity\DealRepository;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use MauticPlugin\MautomicCrmBundle\Entity\StageRepository;
use MauticPlugin\MautomicCrmBundle\Event\DealEvent;
use MauticPlugin\MautomicCrmBundle\Form\Type\DealStageChangedDecisionType;
use MauticPlugin\MautomicCrmBundle\Form\Type\UpdateDealStageActionType;
use MauticPlugin\MautomicCrmBundle\MautomicCrmEvents;
use MauticPlugin\MautomicCrmBundle\Model\DealModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignSubscriber implements EventSubscriberInterface
{
    private DealModel $dealModel;

    /**
     * @param ModelFactory<DealModel> $modelFactory
     */
    public function __construct(
        private RealTimeExecutioner $realTimeExecutioner,
        private ContactTracker $contactTracker,
        ModelFactory $modelFactory,
        private DealRepository $dealRepository,
        private StageRepository $stageRepository,
    ) {
        $model = $modelFactory->getModel('mautomic_crm.deal');
        \assert($model instanceof DealModel);
        $this->dealModel = $model;
    }

    /**
     * @return array<string, array<int, int|string>|array<int, array<int, int|string>>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD                => ['onCampaignBuild', 0],
            MautomicCrmEvents::DEAL_STAGE_CHANGED            => ['onDealStageChanged', 0],
            MautomicCrmEvents::ON_CAMPAIGN_TRIGGER_DECISION  => ['onCampaignTriggerDecision', 0],
            MautomicCrmEvents::ON_CAMPAIGN_BATCH_ACTION      => ['onCampaignTriggerAction', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        $event->addDecision(
            'deal.stage_changed',
            [
                'label'       => 'mautomic_crm.campaign.event.deal_stage_changed',
                'description' => 'mautomic_crm.campaign.event.deal_stage_changed_descr',
                'eventName'   => MautomicCrmEvents::ON_CAMPAIGN_TRIGGER_DECISION,
                'formType'    => DealStageChangedDecisionType::class,
            ]
        );

        $event->addAction(
            'deal.update_stage',
            [
                'label'          => 'mautomic_crm.campaign.event.update_deal_stage',
                'description'    => 'mautomic_crm.campaign.event.update_deal_stage_descr',
                'batchEventName' => MautomicCrmEvents::ON_CAMPAIGN_BATCH_ACTION,
                'formType'       => UpdateDealStageActionType::class,
            ]
        );
    }

    public function onDealStageChanged(DealEvent $event): void
    {
        $deal    = $event->getDeal();
        $contact = $deal->getContact();

        if (null === $contact) {
            return;
        }

        $this->contactTracker->setSystemContact($contact);

        try {
            $this->realTimeExecutioner->execute(
                'deal.stage_changed',
                $event,
                'mautomic_crm.deal',
                $deal->getId()
            );
        } finally {
            $this->contactTracker->setSystemContact(null);
        }
    }

    /** @phpstan-ignore parameter.deprecatedClass */
    public function onCampaignTriggerDecision(CampaignExecutionEvent $event): void
    {
        if (!$event->checkContext('deal.stage_changed')) {
            return;
        }

        $eventDetails = $event->getEventDetails();

        if (!$eventDetails instanceof DealEvent) {
            $event->setResult(false);

            return;
        }

        $config = $event->getConfig();
        $deal   = $eventDetails->getDeal();

        $pipelineId = !empty($config['pipeline']) ? (int) $config['pipeline'] : null;
        $fromStage  = !empty($config['from_stage']) ? (int) $config['from_stage'] : null;
        $toStage    = !empty($config['to_stage']) ? (int) $config['to_stage'] : null;

        if (null !== $pipelineId && (null === $deal->getPipeline() || $deal->getPipeline()->getId() !== $pipelineId)) {
            $event->setResult(false);

            return;
        }

        if (null !== $fromStage && $eventDetails->getPreviousStageId() !== $fromStage) {
            $event->setResult(false);

            return;
        }

        if (null !== $toStage && $eventDetails->getNewStageId() !== $toStage) {
            $event->setResult(false);

            return;
        }

        $event->setResult(true);
    }

    public function onCampaignTriggerAction(PendingEvent $event): void
    {
        if (!$event->checkContext('deal.update_stage')) {
            return;
        }

        $config     = $event->getEvent()->getProperties();
        $pipelineId = !empty($config['pipeline']) ? (int) $config['pipeline'] : null;
        $stageId    = !empty($config['stage']) ? (int) $config['stage'] : null;

        if (null === $pipelineId || null === $stageId) {
            $event->failAll('Pipeline and stage are required.');

            return;
        }

        $stage = $this->stageRepository->find($stageId);

        if (!$stage instanceof Stage) {
            $event->failAll('Target stage not found.');

            return;
        }

        $contacts = $event->getContacts();
        $pending  = $event->getPending();

        foreach ($contacts as $logId => $contact) {
            $deals = $this->dealRepository->getDealsForContact((int) $contact->getId(), $pipelineId);

            if (empty($deals)) {
                $event->fail($pending->get($logId), 'No deal found in the specified pipeline.');
                continue;
            }

            $deal = $deals[0];
            $deal->setStage($stage);
            $this->dealModel->saveEntity($deal);

            $event->pass($pending->get($logId));
        }
    }
}
