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
use MauticPlugin\MautomicCrmBundle\Entity\Task;
use MauticPlugin\MautomicCrmBundle\Model\TaskModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<Task>
 */
class TaskApiController extends CommonApiController
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
        $taskModel = $modelFactory->getModel('mautomic_crm.task');
        \assert($taskModel instanceof TaskModel);

        $this->model           = $taskModel;
        $this->entityClass     = Task::class;
        $this->entityNameOne   = 'task';
        $this->entityNameMulti = 'mautomic_tasks';
        $this->permissionBase  = 'mautomic_crm:tasks';

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    /**
     * @param Task $entity
     */
    protected function createEntityForm($entity): FormInterface
    {
        $form = parent::createEntityForm($entity);

        $form->remove('owner');

        return $form;
    }

    /**
     * @param array<string, mixed> $parameters
     * @param Task                 $entity
     * @param string               $action
     *
     * @return array<string, mixed>
     */
    protected function prepareParametersForBinding(Request $request, $parameters, $entity, $action): array
    {
        if (!array_key_exists('deal', $parameters) && $entity->getDeal()) {
            $parameters['deal'] = $entity->getDeal()->getId();
        }

        if (!array_key_exists('contact', $parameters) && $entity->getContact()) {
            $parameters['contact'] = $entity->getContact()->getId();
        }

        unset($parameters['owner']);

        return $parameters;
    }

    /**
     * @param Task                 $entity
     * @param FormInterface<Task>  $form
     * @param array<string, mixed> $parameters
     * @param string               $action
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
