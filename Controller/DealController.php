<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Model\DealModel;
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
                /** @var Deal $entity */
                $contactId                       = null !== $entity->getContact() ? (int) $entity->getContact()->getId() : null;
                $args['viewParameters']['notes'] = $noteRepo->findByDeal((int) $entity->getId(), $contactId);

                $dealModel = $this->getModel('mautomic_crm.deal');
                \assert($dealModel instanceof DealModel);

                $pipeline = $entity->getPipeline();
                if (null !== $pipeline) {
                    $args['viewParameters']['pipelineStages'] = $dealModel->getStageRepository()->getStagesByPipeline((int) $pipeline->getId());
                } else {
                    $args['viewParameters']['pipelineStages'] = [];
                }
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

    public function quickStageChangeAction(Request $request, int|string $objectId): RedirectResponse
    {
        $dealModel = $this->getModel('mautomic_crm.deal');
        \assert($dealModel instanceof DealModel);

        $deal = $dealModel->getEntity((int) $objectId);

        if (null === $deal) {
            return $this->redirect($this->generateUrl('mautic_mautomic_crm_deal_index'));
        }

        if (!$this->security->hasEntityAccess(
            'mautomic_crm:deals:editown',
            'mautomic_crm:deals:editother',
            $deal->getCreatedBy()
        )) {
            return $this->redirect($this->generateUrl('mautic_mautomic_crm_deal_action', [
                'objectAction' => 'view',
                'objectId'     => $objectId,
            ]));
        }

        $stageId  = (int) $request->request->get('stageId', 0);
        $pipeline = $deal->getPipeline();

        if (null === $pipeline || 0 === $stageId) {
            $this->addFlashMessage('mautomic_crm.deal.stage.change.invalid');

            return $this->redirect($this->generateUrl('mautic_mautomic_crm_deal_action', [
                'objectAction' => 'view',
                'objectId'     => $objectId,
            ]));
        }

        $stages     = $dealModel->getStageRepository()->getStagesByPipeline((int) $pipeline->getId());
        $validStage = null;
        foreach ($stages as $stage) {
            if ($stage->getId() === $stageId) {
                $validStage = $stage;
                break;
            }
        }

        if (null === $validStage) {
            $this->addFlashMessage('mautomic_crm.deal.stage.change.invalid');

            return $this->redirect($this->generateUrl('mautic_mautomic_crm_deal_action', [
                'objectAction' => 'view',
                'objectId'     => $objectId,
            ]));
        }

        $deal->setStage($validStage);
        $dealModel->saveEntity($deal);

        $this->addFlashMessage('mautomic_crm.deal.stage.change.success');

        return $this->redirect($this->generateUrl('mautic_mautomic_crm_deal_action', [
            'objectAction' => 'view',
            'objectId'     => $objectId,
        ]));
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
