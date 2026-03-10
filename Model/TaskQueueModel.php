<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\MautomicCrmBundle\Entity\TaskQueue;
use MauticPlugin\MautomicCrmBundle\Entity\TaskQueueItem;
use MauticPlugin\MautomicCrmBundle\Entity\TaskQueueItemRepository;
use MauticPlugin\MautomicCrmBundle\Entity\TaskQueueRepository;
use MauticPlugin\MautomicCrmBundle\Form\Type\TaskQueueType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * @extends FormModel<TaskQueue>
 */
class TaskQueueModel extends FormModel
{
    public function getActionRouteBase(): string
    {
        return 'mautomic_crm_task_queue';
    }

    public function getPermissionBase(): string
    {
        return 'mautomic_crm:tasks';
    }

    public function getRepository(): TaskQueueRepository
    {
        return $this->em->getRepository(TaskQueue::class);
    }

    public function getItemRepository(): TaskQueueItemRepository
    {
        return $this->em->getRepository(TaskQueueItem::class);
    }

    public function getEntity($id = null): ?TaskQueue
    {
        if (null === $id) {
            $queue = new TaskQueue();
            $queue->setOwner($this->userHelper->getUser());

            return $queue;
        }

        return parent::getEntity($id);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof TaskQueue) {
            throw new MethodNotAllowedHttpException(['TaskQueue']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(TaskQueueType::class, $entity, $options);
    }

    /**
     * @param int[] $taskIds
     */
    public function addTasksToQueue(TaskQueue $queue, array $taskIds): void
    {
        $maxOrder = 0;
        foreach ($queue->getItems() as $item) {
            if ($item->getItemOrder() > $maxOrder) {
                $maxOrder = $item->getItemOrder();
            }
        }

        $taskRepo = $this->em->getRepository(\MauticPlugin\MautomicCrmBundle\Entity\Task::class);

        foreach ($taskIds as $taskId) {
            $task = $taskRepo->find($taskId);
            if (null === $task) {
                continue;
            }

            $item = new TaskQueueItem();
            $item->setQueue($queue);
            $item->setTask($task);
            $item->setItemOrder(++$maxOrder);
            $item->setStatus('pending');

            $queue->addItem($item);
            $this->em->persist($item);
        }

        $this->em->flush();
    }

    public function updateItemStatus(TaskQueueItem $item, string $status): void
    {
        $item->setStatus($status);
        $this->em->flush();
    }

    /**
     * @return array{total: int, completed: int, skipped: int, pending: int}
     */
    public function getQueueStats(int $queueId): array
    {
        return $this->getItemRepository()->getQueueStats($queueId);
    }

    protected function dispatchEvent($action, &$entity, $isNew = false, ?\Symfony\Contracts\EventDispatcher\Event $event = null): ?\Symfony\Contracts\EventDispatcher\Event
    {
        return null;
    }
}
