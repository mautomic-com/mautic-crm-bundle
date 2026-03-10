<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\DealRepository;

class ForecastModel
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @return array{count: int, value: float}
     */
    public function getOpenDeals(?int $pipelineId = null): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('COUNT(d.id) as cnt, COALESCE(SUM(d.amount), 0) as total')
            ->from(Deal::class, 'd')
            ->innerJoin('d.stage', 'st')
            ->where('st.type = :open')
            ->andWhere('d.isPublished = :published')
            ->setParameter('open', 'open')
            ->setParameter('published', true);

        if (null !== $pipelineId) {
            $qb->andWhere('d.pipeline = :pipelineId')
                ->setParameter('pipelineId', $pipelineId);
        }

        $row = $qb->getQuery()->getSingleResult();

        return [
            'count' => (int) $row['cnt'],
            'value' => (float) $row['total'],
        ];
    }

    /**
     * Weighted forecast = SUM(deal.amount * stage.probability / 100).
     */
    public function getWeightedForecast(?int $pipelineId = null): float
    {
        $qb = $this->em->createQueryBuilder()
            ->select('COALESCE(SUM(d.amount * st.probability / 100), 0) as forecast')
            ->from(Deal::class, 'd')
            ->innerJoin('d.stage', 'st')
            ->where('st.type = :open')
            ->andWhere('d.isPublished = :published')
            ->setParameter('open', 'open')
            ->setParameter('published', true);

        if (null !== $pipelineId) {
            $qb->andWhere('d.pipeline = :pipelineId')
                ->setParameter('pipelineId', $pipelineId);
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array{count: int, value: float}
     */
    public function getWonThisMonth(?int $pipelineId = null): array
    {
        return $this->getClosedByType('won', $pipelineId);
    }

    /**
     * @return array{count: int, value: float}
     */
    public function getLostThisMonth(?int $pipelineId = null): array
    {
        return $this->getClosedByType('lost', $pipelineId);
    }

    public function getWinRate(?int $pipelineId = null): float
    {
        $won  = $this->getWonThisMonth($pipelineId);
        $lost = $this->getLostThisMonth($pipelineId);
        $total = $won['count'] + $lost['count'];

        if (0 === $total) {
            return 0.0;
        }

        return round($won['count'] / $total * 100, 1);
    }

    public function getAvgDealSize(?int $pipelineId = null): float
    {
        $qb = $this->em->createQueryBuilder()
            ->select('COALESCE(AVG(d.amount), 0) as avg_size')
            ->from(Deal::class, 'd')
            ->innerJoin('d.stage', 'st')
            ->where('st.type = :won')
            ->andWhere('d.isPublished = :published')
            ->setParameter('won', 'won')
            ->setParameter('published', true);

        if (null !== $pipelineId) {
            $qb->andWhere('d.pipeline = :pipelineId')
                ->setParameter('pipelineId', $pipelineId);
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Average days from creation to won stage.
     */
    public function getAvgSalesCycle(?int $pipelineId = null): float
    {
        $conn = $this->em->getConnection();
        $sql  = 'SELECT AVG(DATEDIFF(d.date_modified, d.date_added)) as avg_days
                 FROM mautomic_deals d
                 INNER JOIN mautomic_stages st ON d.stage_id = st.id
                 WHERE st.stage_type = :stageType
                 AND d.is_published = :published';

        $params = ['stageType' => 'won', 'published' => 1];

        if (null !== $pipelineId) {
            $sql .= ' AND d.pipeline_id = :pipelineId';
            $params['pipelineId'] = $pipelineId;
        }

        $result = $conn->fetchOne($sql, $params);

        return round((float) $result, 1);
    }

    /**
     * Pipeline funnel: conversion by stages.
     *
     * @return array<int, array{name: string, count: int, value: float, probability: int}>
     */
    public function getFunnelData(?int $pipelineId = null): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('st.name, st.probability, st.order as stageOrder, COUNT(d.id) as cnt, COALESCE(SUM(d.amount), 0) as total')
            ->from(Deal::class, 'd')
            ->innerJoin('d.stage', 'st')
            ->where('d.isPublished = :published')
            ->setParameter('published', true)
            ->groupBy('st.id, st.name, st.probability, st.order')
            ->orderBy('st.order', 'ASC');

        if (null !== $pipelineId) {
            $qb->andWhere('d.pipeline = :pipelineId')
                ->setParameter('pipelineId', $pipelineId);
        }

        $rows   = $qb->getQuery()->getResult();
        $result = [];

        foreach ($rows as $row) {
            $result[] = [
                'name'        => (string) $row['name'],
                'count'       => (int) $row['cnt'],
                'value'       => (float) $row['total'],
                'probability' => (int) $row['probability'],
            ];
        }

        return $result;
    }

    /**
     * Revenue over time: won revenue per month for the last 12 months.
     *
     * @return array<int, array{month: string, revenue: float, count: int}>
     */
    public function getRevenueOverTime(?int $pipelineId = null): array
    {
        $conn = $this->em->getConnection();

        $sql = "SELECT DATE_FORMAT(d.date_modified, '%Y-%m') as month,
                       COALESCE(SUM(d.amount), 0) as revenue,
                       COUNT(d.id) as cnt
                FROM mautomic_deals d
                INNER JOIN mautomic_stages st ON d.stage_id = st.id
                WHERE st.stage_type = :stageType
                AND d.is_published = :published
                AND d.date_modified >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";

        $params = ['stageType' => 'won', 'published' => 1];

        if (null !== $pipelineId) {
            $sql .= ' AND d.pipeline_id = :pipelineId';
            $params['pipelineId'] = $pipelineId;
        }

        $sql .= " GROUP BY DATE_FORMAT(d.date_modified, '%Y-%m')
                   ORDER BY month ASC";

        $rows   = $conn->fetchAllAssociative($sql, $params);
        $result = [];

        // Fill missing months with zeroes
        $now   = new \DateTimeImmutable('first day of this month');
        $start = $now->modify('-11 months');

        $monthMap = [];
        foreach ($rows as $row) {
            $monthMap[$row['month']] = $row;
        }

        for ($i = 0; $i < 12; ++$i) {
            $monthKey = $start->modify("+{$i} months")->format('Y-m');
            $result[] = [
                'month'   => $monthKey,
                'revenue' => isset($monthMap[$monthKey]) ? (float) $monthMap[$monthKey]['revenue'] : 0.0,
                'count'   => isset($monthMap[$monthKey]) ? (int) $monthMap[$monthKey]['cnt'] : 0,
            ];
        }

        return $result;
    }

    /**
     * Deals by stage: count and value per stage.
     *
     * @return array<int, array{name: string, count: int, value: float, type: string}>
     */
    public function getDealsByStage(?int $pipelineId = null): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('st.name, st.type, st.order as stageOrder, COUNT(d.id) as cnt, COALESCE(SUM(d.amount), 0) as total')
            ->from(Deal::class, 'd')
            ->innerJoin('d.stage', 'st')
            ->where('d.isPublished = :published')
            ->setParameter('published', true)
            ->groupBy('st.id, st.name, st.type, st.order')
            ->orderBy('st.order', 'ASC');

        if (null !== $pipelineId) {
            $qb->andWhere('d.pipeline = :pipelineId')
                ->setParameter('pipelineId', $pipelineId);
        }

        $rows   = $qb->getQuery()->getResult();
        $result = [];

        foreach ($rows as $row) {
            $result[] = [
                'name'  => (string) $row['name'],
                'count' => (int) $row['cnt'],
                'value' => (float) $row['total'],
                'type'  => (string) $row['type'],
            ];
        }

        return $result;
    }

    /**
     * At-risk deals: close_date in past, still open.
     *
     * @return Deal[]
     */
    public function getAtRiskDeals(?int $pipelineId = null, int $limit = 20): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('d', 'st', 'p', 'o')
            ->from(Deal::class, 'd')
            ->innerJoin('d.stage', 'st')
            ->innerJoin('d.pipeline', 'p')
            ->leftJoin('d.owner', 'o')
            ->where('st.type = :open')
            ->andWhere('d.closeDate < :today')
            ->andWhere('d.isPublished = :published')
            ->setParameter('open', 'open')
            ->setParameter('today', new \DateTime('today'))
            ->setParameter('published', true)
            ->orderBy('d.closeDate', 'ASC')
            ->setMaxResults($limit);

        if (null !== $pipelineId) {
            $qb->andWhere('d.pipeline = :pipelineId')
                ->setParameter('pipelineId', $pipelineId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Top deals: largest open deals.
     *
     * @return Deal[]
     */
    public function getTopDeals(?int $pipelineId = null, int $limit = 10): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('d', 'st', 'p', 'o')
            ->from(Deal::class, 'd')
            ->innerJoin('d.stage', 'st')
            ->innerJoin('d.pipeline', 'p')
            ->leftJoin('d.owner', 'o')
            ->where('st.type = :open')
            ->andWhere('d.isPublished = :published')
            ->setParameter('open', 'open')
            ->setParameter('published', true)
            ->orderBy('d.amount', 'DESC')
            ->setMaxResults($limit);

        if (null !== $pipelineId) {
            $qb->andWhere('d.pipeline = :pipelineId')
                ->setParameter('pipelineId', $pipelineId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Recent activity: latest stage changes, new deals, wins (via audit log).
     *
     * @return array<int, array{deal_name: string, action: string, date: string, amount: float|null}>
     */
    public function getRecentActivity(?int $pipelineId = null, int $limit = 15): array
    {
        $conn = $this->em->getConnection();

        $sql = "SELECT d.name as deal_name, d.amount,
                       CASE
                         WHEN st.stage_type = 'won' THEN 'won'
                         WHEN st.stage_type = 'lost' THEN 'lost'
                         ELSE 'updated'
                       END as action,
                       d.date_modified as date
                FROM mautomic_deals d
                INNER JOIN mautomic_stages st ON d.stage_id = st.id
                WHERE d.is_published = :published";

        $params = ['published' => 1];

        if (null !== $pipelineId) {
            $sql .= ' AND d.pipeline_id = :pipelineId';
            $params['pipelineId'] = $pipelineId;
        }

        $sql .= ' ORDER BY d.date_modified DESC LIMIT :limit';

        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, \Doctrine\DBAL\ParameterType::INTEGER);

        $rows   = $stmt->executeQuery()->fetchAllAssociative();
        $result = [];

        foreach ($rows as $row) {
            $result[] = [
                'deal_name' => (string) $row['deal_name'],
                'action'    => (string) $row['action'],
                'date'      => (string) $row['date'],
                'amount'    => null !== $row['amount'] ? (float) $row['amount'] : null,
            ];
        }

        return $result;
    }

    /**
     * Get all KPI data at once.
     *
     * @return array<string, mixed>
     */
    public function getKpis(?int $pipelineId = null): array
    {
        $openDeals = $this->getOpenDeals($pipelineId);
        $won       = $this->getWonThisMonth($pipelineId);
        $lost      = $this->getLostThisMonth($pipelineId);

        return [
            'open_deals_count'    => $openDeals['count'],
            'open_deals_value'    => $openDeals['value'],
            'weighted_forecast'   => $this->getWeightedForecast($pipelineId),
            'won_count'           => $won['count'],
            'won_value'           => $won['value'],
            'lost_count'          => $lost['count'],
            'lost_value'          => $lost['value'],
            'win_rate'            => $this->getWinRate($pipelineId),
            'avg_deal_size'       => $this->getAvgDealSize($pipelineId),
            'avg_sales_cycle'     => $this->getAvgSalesCycle($pipelineId),
        ];
    }

    /**
     * @return array{count: int, value: float}
     */
    private function getClosedByType(string $type, ?int $pipelineId = null): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('COUNT(d.id) as cnt, COALESCE(SUM(d.amount), 0) as total')
            ->from(Deal::class, 'd')
            ->innerJoin('d.stage', 'st')
            ->where('st.type = :type')
            ->andWhere('d.isPublished = :published')
            ->andWhere('d.dateModified >= :monthStart')
            ->setParameter('type', $type)
            ->setParameter('published', true)
            ->setParameter('monthStart', new \DateTime('first day of this month midnight'));

        if (null !== $pipelineId) {
            $qb->andWhere('d.pipeline = :pipelineId')
                ->setParameter('pipelineId', $pipelineId);
        }

        $row = $qb->getQuery()->getSingleResult();

        return [
            'count' => (int) $row['cnt'],
            'value' => (float) $row['total'],
        ];
    }

    /**
     * Get all forecast data for the API.
     *
     * @return array<string, mixed>
     */
    public function getForecastData(?int $pipelineId = null): array
    {
        return [
            'kpis'            => $this->getKpis($pipelineId),
            'funnel'          => $this->getFunnelData($pipelineId),
            'revenue_over_time' => $this->getRevenueOverTime($pipelineId),
            'deals_by_stage'  => $this->getDealsByStage($pipelineId),
        ];
    }
}
