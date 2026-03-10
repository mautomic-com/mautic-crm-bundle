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
    public function __construct(
        private ForecastModel $forecastModel,
    ) {
    }

    public function indexAction(Request $request): Response
    {
        if (!$this->security->isGranted('mautomic_crm:deals:view')) {
            return $this->accessDenied();
        }

        $pipelineId = $request->query->getInt('pipeline', 0) ?: null;

        $pipelines = $this->getEntityManager()->getRepository(Pipeline::class)
            ->createQueryBuilder('p')
            ->where('p.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

        $kpis           = $this->forecastModel->getKpis($pipelineId);
        $funnelData     = $this->forecastModel->getFunnelData($pipelineId);
        $revenueData    = $this->forecastModel->getRevenueOverTime($pipelineId);
        $dealsByStage   = $this->forecastModel->getDealsByStage($pipelineId);
        $atRiskDeals    = $this->forecastModel->getAtRiskDeals($pipelineId);
        $topDeals       = $this->forecastModel->getTopDeals($pipelineId);
        $recentActivity = $this->forecastModel->getRecentActivity($pipelineId);

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

    private function getEntityManager(): \Doctrine\ORM\EntityManagerInterface
    {
        return $this->container->get('doctrine.orm.entity_manager');
    }
}
