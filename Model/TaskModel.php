<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\MautomicCrmBundle\Entity\Task;
use MauticPlugin\MautomicCrmBundle\Entity\TaskRepository;
use MauticPlugin\MautomicCrmBundle\Form\Type\TaskType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * @extends FormModel<Task>
 */
class TaskModel extends FormModel
{
    public function getActionRouteBase(): string
    {
        return 'mautomic_crm_task';
    }

    public function getPermissionBase(): string
    {
        return 'mautomic_crm:tasks';
    }

    public function getRepository(): TaskRepository
    {
        return $this->em->getRepository(Task::class);
    }

    public function getEntity($id = null): ?Task
    {
        if (null === $id) {
            $task = new Task();
            $task->setOwner($this->userHelper->getUser());

            return $task;
        }

        return parent::getEntity($id);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof Task) {
            throw new MethodNotAllowedHttpException(['Task']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(TaskType::class, $entity, $options);
    }

    protected function dispatchEvent($action, &$entity, $isNew = false, ?\Symfony\Contracts\EventDispatcher\Event $event = null): ?\Symfony\Contracts\EventDispatcher\Event
    {
        return null;
    }
}
