<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\Task;
use MauticPlugin\MautomicCrmBundle\Model\TaskModel;
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
        $dealId = (int) $request->query->get('dealId', '0');

        if ($dealId > 0) {
            return $this->newWithDeal($request, $dealId);
        }

        return parent::newStandard($request);
    }

    /**
     * @return JsonResponse|Response
     */
    public function editAction(Request $request, int|string $objectId, bool $ignorePost = false)
    {
        $dealId = (int) $request->query->get('dealId', '0');

        if ($dealId > 0) {
            return $this->editWithDeal($request, (int) $objectId, $dealId, $ignorePost);
        }

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

    /**
     * @return JsonResponse|RedirectResponse|Response
     */
    private function newWithDeal(Request $request, int $dealId)
    {
        /** @var TaskModel $model */
        $model  = $this->getModel('mautomic_crm.task');
        $entity = $model->getEntity();
        \assert($entity instanceof Task);

        $deal = $this->getModel('mautomic_crm.deal')->getEntity($dealId);
        if ($deal instanceof Deal) {
            $entity->setDeal($deal);
            if (null !== $deal->getContact()) {
                $entity->setContact($deal->getContact());
            }
        }

        $action = $this->generateUrl('mautic_mautomic_crm_task_action', [
            'objectAction' => 'new',
            'dealId'       => $dealId,
        ]);
        $form = $model->createForm($entity, $this->formFactory, $action);

        if (Request::METHOD_POST === $request->getMethod()) {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($this->isFormValid($form)) {
                    $model->saveEntity($entity);

                    return $this->redirectToDeal($entity, $dealId);
                }
            }

            if ($cancelled) {
                return $this->redirectToDeal($entity, $dealId);
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form'   => $form->createView(),
                'entity' => $entity,
            ],
            'contentTemplate' => '@MautomicCrm/Task/form.html.twig',
            'passthroughVars' => [
                'mauticContent' => 'mautomic_crm_task',
                'route'         => $action,
            ],
        ]);
    }

    /**
     * @return JsonResponse|RedirectResponse|Response
     */
    private function editWithDeal(Request $request, int $objectId, int $dealId, bool $ignorePost = false)
    {
        /** @var TaskModel $model */
        $model  = $this->getModel('mautomic_crm.task');
        $entity = $model->getEntity($objectId);

        if (null === $entity) {
            return $this->redirectToDeal(null, $dealId);
        }

        $action = $this->generateUrl('mautic_mautomic_crm_task_action', [
            'objectAction' => 'edit',
            'objectId'     => $objectId,
            'dealId'       => $dealId,
        ]);
        $form = $model->createForm($entity, $this->formFactory, $action);

        if (Request::METHOD_POST === $request->getMethod() && !$ignorePost) {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($this->isFormValid($form)) {
                    $model->saveEntity($entity);

                    return $this->redirectToDeal($entity, $dealId);
                }
            }

            if ($cancelled) {
                return $this->redirectToDeal($entity, $dealId);
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form'   => $form->createView(),
                'entity' => $entity,
            ],
            'contentTemplate' => '@MautomicCrm/Task/form.html.twig',
            'passthroughVars' => [
                'mauticContent' => 'mautomic_crm_task',
                'route'         => $action,
            ],
        ]);
    }

    private function redirectToDeal(?Task $entity, int $dealId): RedirectResponse
    {
        $resolvedDealId = $dealId;

        if ($resolvedDealId < 1 && null !== $entity && null !== $entity->getDeal()) {
            $resolvedDealId = (int) $entity->getDeal()->getId();
        }

        if ($resolvedDealId > 0) {
            return $this->redirect($this->generateUrl('mautic_mautomic_crm_deal_action', [
                'objectAction' => 'view',
                'objectId'     => $resolvedDealId,
            ]));
        }

        return $this->redirect($this->generateUrl('mautic_mautomic_crm_task_index'));
    }
}
