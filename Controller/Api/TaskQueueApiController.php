<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\MautomicCrmBundle\Entity\TaskQueue;
use MauticPlugin\MautomicCrmBundle\Model\TaskQueueModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<TaskQueue>
 */
class TaskQueueApiController extends CommonApiController
{
    public function __construct(
        CorePermissions $security,
        Translator $translator,
        EntityResultHelper $entityResultHelper,
        RouterInterface $router,
        FormFactoryInterface $formFactory,
        AppVersion $appVersion,
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        EventDispatcherInterface $dispatcher,
        CoreParametersHelper $coreParametersHelper,
    ) {
        $taskQueueModel = $modelFactory->getModel('mautomic_crm.task_queue');
        \assert($taskQueueModel instanceof TaskQueueModel);

        $this->model           = $taskQueueModel;
        $this->entityClass     = TaskQueue::class;
        $this->entityNameOne   = 'taskQueue';
        $this->entityNameMulti = 'mautomic_task_queues';
        $this->permissionBase  = 'mautomic_crm:tasks';

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    /**
     * @param TaskQueue $entity
     */
    protected function createEntityForm($entity): FormInterface
    {
        $form = parent::createEntityForm($entity);

        $form->remove('owner');

        return $form;
    }

    /**
     * @param TaskQueue                $entity
     * @param FormInterface<TaskQueue> $form
     * @param array<string, mixed>     $parameters
     * @param string                   $action
     */
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit'): void
    {
        if (!empty($this->entityRequestParameters['owner'])) {
            $ownerId = (int) $this->entityRequestParameters['owner'];
            $em      = $this->doctrine->getManager();
            \assert($em instanceof \Doctrine\ORM\EntityManagerInterface);
            $owner = $em->getReference(User::class, $ownerId);
            $entity->setOwner($owner);
        }
    }
}
