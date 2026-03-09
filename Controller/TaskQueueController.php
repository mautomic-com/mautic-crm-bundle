<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use MauticPlugin\MautomicCrmBundle\Entity\TaskQueueItem;
use MauticPlugin\MautomicCrmBundle\Model\TaskQueueModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TaskQueueController extends AbstractStandardFormController
{
    protected function getTemplateBase(): string
    {
        return '@MautomicCrm/TaskQueue';
    }

    protected function getModelName(): string
    {
        return 'mautomic_crm.task_queue';
    }

    protected function getIndexRoute(): string
    {
        return 'mautic_mautomic_crm_task_queue_index';
    }

    protected function getActionRoute(): string
    {
        return 'mautic_mautomic_crm_task_queue_action';
    }

    protected function getPermissionBase(): string
    {
        return 'mautomic_crm:tasks';
    }

    protected function getDefaultOrderColumn(): string
    {
        return 'name';
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
        return parent::viewStandard($request, $objectId, 'mautomic_crm.task_queue', 'mautomic_crm');
    }

    /**
     * @return JsonResponse|RedirectResponse
     */
    public function deleteAction(Request $request, int|string $objectId)
    {
        return parent::deleteStandard($request, $objectId);
    }

    public function focusAction(Request $request, int|string $objectId): Response
    {
        $model = $this->getModel('mautomic_crm.task_queue');
        \assert($model instanceof TaskQueueModel);

        $queue = $model->getEntity((int) $objectId);

        if (null === $queue) {
            return $this->notFound();
        }

        $itemRepo    = $model->getItemRepository();
        $currentItem = $itemRepo->getNextPendingItem((int) $queue->getId());
        $stats       = $itemRepo->getQueueStats((int) $queue->getId());

        return $this->delegateView([
            'viewParameters' => [
                'queue'       => $queue,
                'currentItem' => $currentItem,
                'stats'       => $stats,
            ],
            'contentTemplate' => '@MautomicCrm/TaskQueue/focus.html.twig',
            'passthroughVars' => [
                'mauticContent' => 'mautomic_crm_task_queue',
                'route'         => $this->generateUrl('mautic_mautomic_crm_task_queue_action', [
                    'objectAction' => 'focus',
                    'objectId'     => $objectId,
                ]),
            ],
        ]);
    }

    public function focusUpdateAction(Request $request, int|string $objectId): JsonResponse
    {
        $model = $this->getModel('mautomic_crm.task_queue');
        \assert($model instanceof TaskQueueModel);

        $queue = $model->getEntity((int) $objectId);

        if (null === $queue) {
            return new JsonResponse(['success' => false, 'message' => 'Queue not found'], Response::HTTP_NOT_FOUND);
        }

        $itemId = (int) $request->request->get('itemId', 0);
        $action = $request->request->get('action', 'completed');

        $itemRepo = $model->getItemRepository();
        $item     = $itemRepo->find($itemId);

        if (!$item instanceof TaskQueueItem || $item->getQueue()?->getId() !== $queue->getId()) {
            return new JsonResponse(['success' => false, 'message' => 'Item not found'], Response::HTTP_NOT_FOUND);
        }

        if ('completed' === $action) {
            $model->updateItemStatus($item, 'completed');
            $task = $item->getTask();
            if (null !== $task) {
                $task->setStatus('completed');
                $taskModel = $this->getModel('mautomic_crm.task');
                \assert($taskModel instanceof \MauticPlugin\MautomicCrmBundle\Model\TaskModel);
                $taskModel->saveEntity($task);
            }
        } elseif ('skipped' === $action) {
            $model->updateItemStatus($item, 'skipped');
        }

        $nextItem = $itemRepo->getNextPendingItem((int) $queue->getId());
        $stats    = $itemRepo->getQueueStats((int) $queue->getId());

        return new JsonResponse([
            'success'  => true,
            'stats'    => $stats,
            'nextItem' => null !== $nextItem ? [
                'id'       => $nextItem->getId(),
                'taskId'   => $nextItem->getTask()?->getId(),
                'title'    => $nextItem->getTask()?->getTitle(),
                'dueDate'  => $nextItem->getTask()?->getDueDate()?->format('Y-m-d H:i'),
                'priority' => $nextItem->getTask()?->getPriority(),
                'deal'     => $nextItem->getTask()?->getDeal()?->getName(),
                'contact'  => $nextItem->getTask()?->getContact()?->getName(),
            ] : null,
        ]);
    }

    public function addTasksAction(Request $request, int|string $objectId): JsonResponse
    {
        $model = $this->getModel('mautomic_crm.task_queue');
        \assert($model instanceof TaskQueueModel);

        $queue = $model->getEntity((int) $objectId);

        if (null === $queue) {
            return new JsonResponse(['success' => false, 'message' => 'Queue not found'], Response::HTTP_NOT_FOUND);
        }

        /** @var array<int> $taskIds */
        $taskIds = array_map('intval', (array) $request->request->all('taskIds'));

        if (empty($taskIds)) {
            return new JsonResponse(['success' => false, 'message' => 'No tasks provided'], Response::HTTP_BAD_REQUEST);
        }

        $model->addTasksToQueue($queue, $taskIds);

        return new JsonResponse(['success' => true, 'added' => \count($taskIds)]);
    }
}
