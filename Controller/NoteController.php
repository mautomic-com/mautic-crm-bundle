<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\Note;
use MauticPlugin\MautomicCrmBundle\Model\NoteModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NoteController extends AbstractStandardFormController
{
    protected function getTemplateBase(): string
    {
        return '@MautomicCrm/Note';
    }

    protected function getModelName(): string
    {
        return 'mautomic_crm.note';
    }

    protected function getIndexRoute(): string
    {
        return 'mautic_mautomic_crm_note_action';
    }

    protected function getActionRoute(): string
    {
        return 'mautic_mautomic_crm_note_action';
    }

    protected function getPermissionBase(): string
    {
        return 'mautomic_crm:notes';
    }

    /**
     * @return JsonResponse|RedirectResponse|Response
     */
    public function newAction(Request $request)
    {
        /** @var NoteModel $model */
        $model  = $this->getModel('mautomic_crm.note');
        $entity = $model->getEntity();
        \assert($entity instanceof Note);

        $dealId = (int) $request->query->get('dealId', '0');

        if ($dealId > 0) {
            $deal = $this->getModel('mautomic_crm.deal')->getEntity($dealId);
            if ($deal instanceof Deal) {
                $entity->setDeal($deal);
                if (null !== $deal->getContact()) {
                    $entity->setContact($deal->getContact());
                }
            }
        }

        $action = $this->generateUrl('mautic_mautomic_crm_note_action', ['objectAction' => 'new']);
        $form   = $model->createForm($entity, $this->formFactory, $action);

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
            'contentTemplate' => '@MautomicCrm/Note/form.html.twig',
            'passthroughVars' => [
                'mauticContent' => 'mautomic_crm_note',
                'route'         => $action,
            ],
        ]);
    }

    /**
     * @return JsonResponse|RedirectResponse|Response
     */
    public function editAction(Request $request, int|string $objectId, bool $ignorePost = false)
    {
        /** @var NoteModel $model */
        $model  = $this->getModel('mautomic_crm.note');
        $entity = $model->getEntity((int) $objectId);

        if (null === $entity) {
            return $this->postActionRedirect([
                'returnUrl'       => $this->generateUrl('mautic_mautomic_crm_deal_index'),
                'contentTemplate' => '@MautomicCrm/Deal/index.html.twig',
            ]);
        }

        $dealId = (int) $request->query->get('dealId', '0');
        $action = $this->generateUrl('mautic_mautomic_crm_note_action', [
            'objectAction' => 'edit',
            'objectId'     => $objectId,
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
            'contentTemplate' => '@MautomicCrm/Note/form.html.twig',
            'passthroughVars' => [
                'mauticContent' => 'mautomic_crm_note',
                'route'         => $action,
            ],
        ]);
    }

    /**
     * @return JsonResponse|RedirectResponse
     */
    public function deleteAction(Request $request, int|string $objectId)
    {
        /** @var NoteModel $model */
        $model  = $this->getModel('mautomic_crm.note');
        $entity = $model->getEntity((int) $objectId);

        $dealId = (int) $request->query->get('dealId', '0');

        if (null !== $entity) {
            $model->deleteEntity($entity);
        }

        return $this->redirectToDeal($entity, $dealId);
    }

    private function redirectToDeal(?Note $entity, int $dealId): RedirectResponse
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

        return $this->redirect($this->generateUrl('mautic_mautomic_crm_deal_index'));
    }
}
