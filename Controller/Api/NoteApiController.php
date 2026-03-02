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
use MauticPlugin\MautomicCrmBundle\Entity\Note;
use MauticPlugin\MautomicCrmBundle\Model\NoteModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<Note>
 */
class NoteApiController extends CommonApiController
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
        $noteModel = $modelFactory->getModel('mautomic_crm.note');
        \assert($noteModel instanceof NoteModel);

        $this->model           = $noteModel;
        $this->entityClass     = Note::class;
        $this->entityNameOne   = 'note';
        $this->entityNameMulti = 'mautomic_notes';
        $this->permissionBase  = 'mautomic_crm:notes';

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    /**
     * @param array<string, mixed> $parameters
     * @param Note                 $entity
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

        return $parameters;
    }
}
