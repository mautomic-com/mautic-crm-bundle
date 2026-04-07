<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CoreBundle\Factory\ModelFactory;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\PipelineRepository;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use MauticPlugin\MautomicCrmBundle\Entity\StageRepository;
use MauticPlugin\MautomicCrmBundle\Form\Type\CreateDealActionType;
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
        ModelFactory $modelFactory,
        private PipelineRepository $pipelineRepository,
        private StageRepository $stageRepository,
    ) {
        $model = $modelFactory->getModel('mautomic_crm.deal');
        \assert($model instanceof DealModel);
        $this->dealModel = $model;
    }

    /**
     * @return array<string, array<int, int|string>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD                   => ['onCampaignBuild', 0],
            MautomicCrmEvents::ON_CAMPAIGN_CREATE_DEAL_ACTION   => ['onCampaignCreateDeal', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        $event->addAction(
            'deal.create',
            [
                'label'          => 'mautomic_crm.campaign.event.create_deal',
                'description'    => 'mautomic_crm.campaign.event.create_deal_descr',
                'batchEventName' => MautomicCrmEvents::ON_CAMPAIGN_CREATE_DEAL_ACTION,
                'formType'       => CreateDealActionType::class,
            ]
        );
    }

    public function onCampaignCreateDeal(PendingEvent $event): void
    {
        if (!$event->checkContext('deal.create')) {
            return;
        }

        $config = $event->getEvent()->getProperties();

        $pipeline = $this->resolvePipeline($config);

        if (!$pipeline instanceof Pipeline) {
            $event->failAll('Pipeline not found.');

            return;
        }

        $stage = $this->resolveStage($config, $pipeline);

        if (!$stage instanceof Stage) {
            $event->failAll('No stage available for the selected pipeline.');

            return;
        }

        $dealName    = !empty($config['name']) ? (string) $config['name'] : 'Campaign Deal';
        $description = !empty($config['description']) ? (string) $config['description'] : null;
        $amount      = !empty($config['amount']) ? (string) $config['amount'] : null;
        $currency    = !empty($config['currency']) ? (string) $config['currency'] : null;

        $contacts = $event->getContacts();
        $pending  = $event->getPending();

        foreach ($contacts as $logId => $contact) {
            $deal = new Deal();
            $deal->setName($dealName);
            $deal->setPipeline($pipeline);
            $deal->setStage($stage);
            $deal->setContact($contact);
            $deal->setIsPublished(true);

            if (null !== $description) {
                $deal->setDescription($description);
            }

            if (null !== $amount) {
                $deal->setAmount($amount);
            }

            if (null !== $currency) {
                $deal->setCurrency($currency);
            }

            $this->dealModel->saveEntity($deal);

            $event->pass($pending->get($logId));
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function resolvePipeline(array $config): ?Pipeline
    {
        $pipelineId = !empty($config['pipeline']) ? (int) $config['pipeline'] : null;

        if (null === $pipelineId) {
            return null;
        }

        $pipeline = $this->pipelineRepository->find($pipelineId);

        return $pipeline instanceof Pipeline ? $pipeline : null;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function resolveStage(array $config, Pipeline $pipeline): ?Stage
    {
        $stageId = !empty($config['stage']) ? (int) $config['stage'] : null;

        if (null !== $stageId) {
            $stage = $this->stageRepository->find($stageId);

            if ($stage instanceof Stage) {
                return $stage;
            }
        }

        $firstStage = $pipeline->getStages()->first();

        return $firstStage instanceof Stage ? $firstStage : null;
    }
}
