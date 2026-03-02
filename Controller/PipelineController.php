<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use MauticPlugin\MautomicCrmBundle\Entity\DealRepository;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\PipelineRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PipelineController extends AbstractStandardFormController
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
            /** @var Pipeline|null $entity */
            $entity = $args['viewParameters']['item'] ?? null;

            if (null !== $entity && null !== $entity->getId()) {
                /** @var DealRepository $dealRepo */
                $dealRepo = $this->getModel('mautomic_crm.deal')->getRepository();
                $deals    = $dealRepo->getDealsForBoard((int) $entity->getId());

                $boardData = [];
                foreach ($entity->getStages() as $stage) {
                    $boardData[$stage->getId()] = [
                        'stage'       => $stage,
                        'deals'       => [],
                        'count'       => 0,
                        'totalAmount' => 0.0,
                    ];
                }

                foreach ($deals as $deal) {
                    $stageId = $deal->getStage()?->getId();
                    if (null !== $stageId && isset($boardData[$stageId])) {
                        $boardData[$stageId]['deals'][] = $deal;
                        ++$boardData[$stageId]['count'];
                        $boardData[$stageId]['totalAmount'] += (float) ($deal->getAmount() ?? '0');
                    }
                }

                $args['viewParameters']['boardData'] = $boardData;

                /** @var PipelineRepository $pipelineRepo */
                $pipelineRepo                            = $this->getModel('mautomic_crm.pipeline')->getRepository();
                $args['viewParameters']['allPipelines']  = $pipelineRepo->findBy(['isPublished' => true], ['name' => 'ASC']);
            }
        }

        return $args;
    }

    protected function getTemplateBase(): string
    {
        return '@MautomicCrm/Pipeline';
    }

    protected function getModelName(): string
    {
        return 'mautomic_crm.pipeline';
    }

    protected function getIndexRoute(): string
    {
        return 'mautic_mautomic_crm_pipeline_index';
    }

    protected function getActionRoute(): string
    {
        return 'mautic_mautomic_crm_pipeline_action';
    }

    protected function getPermissionBase(): string
    {
        return 'mautomic_crm:pipelines';
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
        return parent::viewStandard($request, $objectId, 'mautomic_crm.pipeline', 'mautomic_crm');
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
