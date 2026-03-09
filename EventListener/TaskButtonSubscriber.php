<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomButtonEvent;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Twig\Helper\ButtonHelper;
use MauticPlugin\MautomicCrmBundle\Entity\TaskQueueRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TaskButtonSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private RouterInterface $router,
        private CorePermissions $security,
        private TaskQueueRepository $taskQueueRepository,
        private UserHelper $userHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_BUTTONS => ['injectViewButtons', 0],
        ];
    }

    public function injectViewButtons(CustomButtonEvent $event): void
    {
        if (!str_contains($event->getRoute(), 'mautic_mautomic_crm_task_index')) {
            return;
        }

        if (!$this->security->isGranted('mautomic_crm:tasks:view')) {
            return;
        }

        $queues = $this->taskQueueRepository->findAccessibleQueues(
            (int) ($this->userHelper->getUser()->getId() ?? 0)
        );

        if (0 === \count($queues)) {
            return;
        }

        foreach ($queues as $queue) {
            $route = $this->router->generate('mautic_mautomic_crm_task_action', [
                'objectAction' => 'batchAddToQueue',
            ]);

            $event->addButton(
                [
                    'attr' => [
                        'data-toggle'           => 'confirmation',
                        'href'                  => $route.'?queueId='.$queue->getId(),
                        'data-precheck'         => 'batchActionPrecheck',
                        'data-message'          => $this->translator->trans(
                            'mautomic_crm.task_queue.confirm_add',
                            ['%queue%' => $queue->getName()]
                        ),
                        'data-confirm-text'     => $this->translator->trans('mautomic_crm.task_queue.add_to_queue'),
                        'data-confirm-callback' => 'executeBatchAction',
                        'data-cancel-text'      => $this->translator->trans('mautic.core.form.cancel'),
                        'data-cancel-callback'  => 'dismissConfirmation',
                    ],
                    'btnText'   => $queue->getName(),
                    'iconClass' => 'ri-play-list-add-line',
                ],
                ButtonHelper::LOCATION_BULK_ACTIONS
            );
        }
    }
}
