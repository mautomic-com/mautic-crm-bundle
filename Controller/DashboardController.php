<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Model\ForecastModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends CommonController
{
    public function indexAction(Request $request): Response
    {
        if (!$this->security->isGranted('mautomic_crm:deals:view')) {
            return $this->accessDenied();
        }

        /** @var ForecastModel $forecastModel */
        $forecastModel = $this->getModel('mautomic_crm.forecast');

        $pipelineId = $request->query->getInt('pipeline', 0) ?: null;

        $pipelines = $this->getDoctrine()->getRepository(Pipeline::class)
            ->createQueryBuilder('p')
            ->where('p.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

        $kpis           = $forecastModel->getKpis($pipelineId);
        $funnelData     = $forecastModel->getFunnelData($pipelineId);
        $revenueData    = $forecastModel->getRevenueOverTime($pipelineId);
        $dealsByStage   = $forecastModel->getDealsByStage($pipelineId);
        $atRiskDeals    = $forecastModel->getAtRiskDeals($pipelineId);
        $topDeals       = $forecastModel->getTopDeals($pipelineId);
        $recentActivity = $forecastModel->getRecentActivity($pipelineId);

        return $this->delegateView([
            'viewParameters' => [
                'kpis'           => $kpis,
                'funnelData'     => $funnelData,
                'revenueData'    => $revenueData,
                'dealsByStage'   => $dealsByStage,
                'atRiskDeals'    => $atRiskDeals,
                'topDeals'       => $topDeals,
                'recentActivity' => $recentActivity,
                'pipelines'      => $pipelines,
                'pipelineId'     => $pipelineId,
            ],
            'contentTemplate' => '@MautomicCrm/Dashboard/index.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautomic_crm_dashboard',
                'mauticContent' => 'mautomicCrmDashboard',
                'route'         => $this->generateUrl('mautic_mautomic_crm_dashboard_index'),
            ],
        ]);
    }
}
