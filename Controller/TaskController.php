<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use MauticPlugin\MautomicCrmBundle\Model\TaskQueueModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends AbstractStandardFormController
{
    protected function getTemplateBase(): string
    {
        return '@MautomicCrm/Task';
    }

    protected function getModelName(): string
    {
        return 'mautomic_crm.task';
    }

    protected function getIndexRoute(): string
    {
        return 'mautic_mautomic_crm_task_index';
    }

    protected function getActionRoute(): string
    {
        return 'mautic_mautomic_crm_task_action';
    }

    protected function getPermissionBase(): string
    {
        return 'mautomic_crm:tasks';
    }

    protected function getDefaultOrderColumn(): string
    {
        return 'title';
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
        return parent::viewStandard($request, $objectId, 'mautomic_crm.task', 'mautomic_crm');
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

    /**
     * @return JsonResponse|RedirectResponse|Response
     */
    public function batchAddToQueueAction(Request $request)
    {
        $page      = $request->getSession()->get('mautic.mautomic_crm_task.page', 1);
        $returnUrl = $this->generateUrl($this->getIndexRoute(), ['page' => $page]);
        $flashes   = [];

        if ('POST' === $request->getMethod()) {
            $queueId = (int) $request->query->get('queueId', '0');
            $ids     = json_decode($request->query->get('ids', '[]'), true);

            $queueModel = $this->getModel('mautomic_crm.task_queue');
            \assert($queueModel instanceof TaskQueueModel);

            $queue = $queueModel->getEntity($queueId);

            if (null === $queue || 0 === $queueId) {
                $flashes[] = [
                    'type' => 'error',
                    'msg'  => 'mautomic_crm.task_queue.error.notfound',
                ];
            } elseif (!\is_array($ids) || 0 === \count($ids)) {
                $flashes[] = [
                    'type' => 'error',
                    'msg'  => 'mautomic_crm.task_queue.error.no_tasks',
                ];
            } else {
                $taskIds = array_map('intval', $ids);
                $queueModel->addTasksToQueue($queue, $taskIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautomic_crm.task_queue.batch_add.success',
                    'msgVars' => [
                        '%count%' => \count($taskIds),
                        '%queue%' => $queue->getName(),
                    ],
                ];
            }
        }

        return $this->postActionRedirect(
            array_merge([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $page],
                'contentTemplate' => self::class.'::indexAction',
                'passthroughVars' => [
                    'mauticContent' => 'mautomic_crm_task',
                ],
                'flashes' => $flashes,
            ])
        );
    }
}
