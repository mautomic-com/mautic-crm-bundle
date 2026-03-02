<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DealController extends AbstractStandardFormController
{
    /**
     * @param array<string, mixed> $args
     * @param mixed                $action
     *
     * @return array<string, mixed>
     */
    protected function getViewArguments(array $args, $action): array
    {
        if ('view' === $action) {
            $entity = $args['viewParameters']['item'] ?? null;
            if (null !== $entity && method_exists($entity, 'getId')) {
                /** @var \MauticPlugin\MautomicCrmBundle\Entity\TaskRepository $taskRepo */
                $taskRepo                        = $this->getModel('mautomic_crm.task')->getRepository();
                $args['viewParameters']['tasks'] = $taskRepo->findByDeal((int) $entity->getId());

                /** @var \MauticPlugin\MautomicCrmBundle\Entity\NoteRepository $noteRepo */
                $noteRepo                        = $this->getModel('mautomic_crm.note')->getRepository();
                /** @var \MauticPlugin\MautomicCrmBundle\Entity\Deal $entity */
                $contactId                       = null !== $entity->getContact() ? (int) $entity->getContact()->getId() : null;
                $args['viewParameters']['notes'] = $noteRepo->findByDeal((int) $entity->getId(), $contactId);
            }
        }

        return $args;
    }

    protected function getTemplateBase(): string
    {
        return '@MautomicCrm/Deal';
    }

    protected function getModelName(): string
    {
        return 'mautomic_crm.deal';
    }

    protected function getIndexRoute(): string
    {
        return 'mautic_mautomic_crm_deal_index';
    }

    protected function getActionRoute(): string
    {
        return 'mautic_mautomic_crm_deal_action';
    }

    protected function getPermissionBase(): string
    {
        return 'mautomic_crm:deals';
    }

    public function indexAction(Request $request, int $page = 1): Response
    {
        return parent::indexStandard($request, $page);
    }

    /**
     * @return JsonResponse|Response
     */
    public function newAction(Request $request)
    {
        return parent::newStandard($request);
    }

    /**
     * @return JsonResponse|Response
     */
    public function editAction(Request $request, int|string $objectId, bool $ignorePost = false)
    {
        return parent::editStandard($request, $objectId, $ignorePost);
    }

    /**
     * @return array<string, mixed>|JsonResponse|RedirectResponse|Response
     */
    public function viewAction(Request $request, int|string $objectId): array|JsonResponse|RedirectResponse|Response
    {
        return parent::viewStandard($request, $objectId, 'mautomic_crm.deal', 'mautomic_crm');
    }

    /**
     * @return JsonResponse|RedirectResponse|Response
     */
    public function cloneAction(Request $request, int|string $objectId)
    {
        return parent::cloneStandard($request, $objectId);
    }

    /**
     * @return JsonResponse|RedirectResponse
     */
    public function deleteAction(Request $request, int|string $objectId)
    {
        return parent::deleteStandard($request, $objectId);
    }

    /**
     * @return JsonResponse|RedirectResponse
     */
    public function batchDeleteAction(Request $request)
    {
        return parent::batchDeleteStandard($request);
    }
}
