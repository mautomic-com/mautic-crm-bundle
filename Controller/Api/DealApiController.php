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
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Model\DealModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<Deal>
 */
class DealApiController extends CommonApiController
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
        $dealModel = $modelFactory->getModel('mautomic_crm.deal');
        \assert($dealModel instanceof DealModel);

        $this->model           = $dealModel;
        $this->entityClass     = Deal::class;
        $this->entityNameOne   = 'deal';
        $this->entityNameMulti = 'mautomic_deals';
        $this->permissionBase  = 'mautomic_crm:deals';

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    /**
     * @param Deal $entity
     */
    protected function createEntityForm($entity): FormInterface
    {
        $form = parent::createEntityForm($entity);

        $form->remove('owner');

        return $form;
    }

    /**
     * @param array<string, mixed> $parameters
     * @param Deal                 $entity
     * @param string               $action
     *
     * @return array<string, mixed>
     */
    protected function prepareParametersForBinding(Request $request, $parameters, $entity, $action): array
    {
        if (!array_key_exists('pipeline', $parameters) && $entity->getPipeline()) {
            $parameters['pipeline'] = $entity->getPipeline()->getId();
        }

        if (!array_key_exists('stage', $parameters) && $entity->getStage()) {
            $parameters['stage'] = $entity->getStage()->getId();
        }

        unset($parameters['owner']);

        return $parameters;
    }

    /**
     * @param Deal                 $entity
     * @param FormInterface<Deal>  $form
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
