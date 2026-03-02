<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DealFieldController extends AbstractStandardFormController
{
    protected function getTemplateBase(): string
    {
        return '@MautomicCrm/DealField';
    }

    protected function getModelName(): string
    {
        return 'mautomic_crm.deal_field';
    }

    protected function getIndexRoute(): string
    {
        return 'mautic_mautomic_crm_deal_field_index';
    }

    protected function getActionRoute(): string
    {
        return 'mautic_mautomic_crm_deal_field_action';
    }

    protected function getPermissionBase(): string
    {
        return 'mautomic_crm:deals';
    }

    protected function getDefaultOrderColumn(): string
    {
        return 'label';
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
