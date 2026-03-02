<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use MauticPlugin\MautomicCrmBundle\Event\DealEvent;
use MauticPlugin\MautomicCrmBundle\MautomicCrmEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DealSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
        private EventDispatcherInterface $dispatcher,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @return array<string, array<int, int|string>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            MautomicCrmEvents::DEAL_POST_SAVE => ['onDealPostSave', 0],
        ];
    }

    public function onDealPostSave(DealEvent $event): void
    {
        $deal = $event->getDeal();

        $details = $event->getChanges();

        unset($details['dateModified']);

        if (empty($details)) {
            return;
        }

        $log = [
            'bundle'    => 'mautomic_crm',
            'object'    => 'mautomic_crm.deal',
            'objectId'  => $deal->getId(),
            'action'    => $event->isNew() ? 'create' : 'update',
            'details'   => $details,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);

        if (isset($details['stage'])) {
            $stageChange = $details['stage'];
            $stageEvent  = new DealEvent($deal, false);
            $stageEvent->setEntityManager($this->em);

            if (\is_array($stageChange) && 2 === \count($stageChange)) {
                $oldStage = $stageChange[0];
                $newStage = $stageChange[1];
                $stageEvent->setPreviousStageId($oldStage instanceof \MauticPlugin\MautomicCrmBundle\Entity\Stage ? $oldStage->getId() : null);
                $stageEvent->setNewStageId($newStage instanceof \MauticPlugin\MautomicCrmBundle\Entity\Stage ? $newStage->getId() : null);
            }

            $this->dispatcher->dispatch($stageEvent, MautomicCrmEvents::DEAL_STAGE_CHANGED);
        }
    }
}
