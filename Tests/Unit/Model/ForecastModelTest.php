<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Model;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use MauticPlugin\MautomicCrmBundle\Model\ForecastModel;
use PHPUnit\Framework\TestCase;

class ForecastModelTest extends TestCase
{
    private ForecastModel $model;

    /** @var EntityManagerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private EntityManagerInterface $em;

    /** @var QueryBuilder&\PHPUnit\Framework\MockObject\MockObject */
    private QueryBuilder $qb;

    /** @var AbstractQuery&\PHPUnit\Framework\MockObject\MockObject */
    private AbstractQuery $query;

    protected function setUp(): void
    {
        $this->em    = $this->createMock(EntityManagerInterface::class);
        $this->qb    = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(AbstractQuery::class);

        // Make QueryBuilder methods chainable
        $this->qb->method('select')->willReturnSelf();
        $this->qb->method('from')->willReturnSelf();
        $this->qb->method('innerJoin')->willReturnSelf();
        $this->qb->method('leftJoin')->willReturnSelf();
        $this->qb->method('where')->willReturnSelf();
        $this->qb->method('andWhere')->willReturnSelf();
        $this->qb->method('setParameter')->willReturnSelf();
        $this->qb->method('groupBy')->willReturnSelf();
        $this->qb->method('orderBy')->willReturnSelf();
        $this->qb->method('addOrderBy')->willReturnSelf();
        $this->qb->method('setMaxResults')->willReturnSelf();
        $this->qb->method('getQuery')->willReturn($this->query);

        $this->em->method('createQueryBuilder')->willReturn($this->qb);

        $this->model = new ForecastModel($this->em);
    }

    public function testGetOpenDealsReturnsCountAndValue(): void
    {
        $this->query->method('getSingleResult')
            ->willReturn(['cnt' => '5', 'total' => '50000.00']);

        $result = $this->model->getOpenDeals();

        $this->assertSame(5, $result['count']);
        $this->assertSame(50000.0, $result['value']);
    }

    public function testGetOpenDealsWithZeroDeals(): void
    {
        $this->query->method('getSingleResult')
            ->willReturn(['cnt' => '0', 'total' => '0']);

        $result = $this->model->getOpenDeals();

        $this->assertSame(0, $result['count']);
        $this->assertSame(0.0, $result['value']);
    }

    public function testGetWeightedForecast(): void
    {
        $this->query->method('getSingleScalarResult')
            ->willReturn('35000.00');

        $result = $this->model->getWeightedForecast();

        $this->assertSame(35000.0, $result);
    }

    public function testGetWinRateWithNoDeals(): void
    {
        // Both won and lost return 0
        $this->query->method('getSingleResult')
            ->willReturn(['cnt' => '0', 'total' => '0']);

        $result = $this->model->getWinRate();

        $this->assertSame(0.0, $result);
    }

    public function testGetWinRateWithDeals(): void
    {
        $callCount = 0;
        $this->query->method('getSingleResult')
            ->willReturnCallback(function () use (&$callCount) {
                ++$callCount;
                if (1 === $callCount) {
                    // Won
                    return ['cnt' => '3', 'total' => '30000'];
                }

                // Lost
                return ['cnt' => '2', 'total' => '20000'];
            });

        $result = $this->model->getWinRate();

        $this->assertSame(60.0, $result);
    }

    public function testGetAvgDealSize(): void
    {
        $this->query->method('getSingleScalarResult')
            ->willReturn('10000.00');

        $result = $this->model->getAvgDealSize();

        $this->assertSame(10000.0, $result);
    }

    public function testGetAvgSalesCycle(): void
    {
        $conn = $this->createMock(Connection::class);
        $conn->method('fetchOne')->willReturn('14.5');

        $this->em->method('getConnection')->willReturn($conn);

        $result = $this->model->getAvgSalesCycle();

        $this->assertSame(14.5, $result);
    }

    public function testGetAvgSalesCycleWithNullResult(): void
    {
        $conn = $this->createMock(Connection::class);
        $conn->method('fetchOne')->willReturn(false);

        $this->em->method('getConnection')->willReturn($conn);

        $result = $this->model->getAvgSalesCycle();

        $this->assertSame(0.0, $result);
    }

    public function testGetFunnelData(): void
    {
        $this->query->method('getResult')
            ->willReturn([
                ['name' => 'Qualification', 'probability' => 20, 'stageOrder' => 1, 'cnt' => '10', 'total' => '100000'],
                ['name' => 'Proposal', 'probability' => 60, 'stageOrder' => 2, 'cnt' => '5', 'total' => '75000'],
                ['name' => 'Negotiation', 'probability' => 80, 'stageOrder' => 3, 'cnt' => '3', 'total' => '60000'],
            ]);

        $result = $this->model->getFunnelData();

        $this->assertCount(3, $result);
        $this->assertSame('Qualification', $result[0]['name']);
        $this->assertSame(10, $result[0]['count']);
        $this->assertSame(100000.0, $result[0]['value']);
        $this->assertSame(20, $result[0]['probability']);
    }

    public function testGetDealsByStage(): void
    {
        $this->query->method('getResult')
            ->willReturn([
                ['name' => 'Open', 'type' => 'open', 'stageOrder' => 1, 'cnt' => '8', 'total' => '80000'],
                ['name' => 'Won', 'type' => 'won', 'stageOrder' => 2, 'cnt' => '3', 'total' => '30000'],
            ]);

        $result = $this->model->getDealsByStage();

        $this->assertCount(2, $result);
        $this->assertSame('Open', $result[0]['name']);
        $this->assertSame('open', $result[0]['type']);
        $this->assertSame(8, $result[0]['count']);
    }

    public function testGetRevenueOverTimeReturns12Months(): void
    {
        $conn   = $this->createMock(Connection::class);
        $conn->method('fetchAllAssociative')
            ->willReturn([]);

        $this->em->method('getConnection')->willReturn($conn);

        $result = $this->model->getRevenueOverTime();

        $this->assertCount(12, $result);

        foreach ($result as $month) {
            $this->assertArrayHasKey('month', $month);
            $this->assertArrayHasKey('revenue', $month);
            $this->assertArrayHasKey('count', $month);
            $this->assertSame(0.0, $month['revenue']);
            $this->assertSame(0, $month['count']);
        }
    }

    public function testGetRevenueOverTimeFillsData(): void
    {
        $now       = new \DateTimeImmutable('first day of this month');
        $thisMonth = $now->format('Y-m');

        $conn = $this->createMock(Connection::class);
        $conn->method('fetchAllAssociative')
            ->willReturn([
                ['month' => $thisMonth, 'revenue' => '15000.00', 'cnt' => '2'],
            ]);

        $this->em->method('getConnection')->willReturn($conn);

        $result = $this->model->getRevenueOverTime();

        $this->assertCount(12, $result);

        // Last month should have data
        $lastEntry = end($result);
        $this->assertSame($thisMonth, $lastEntry['month']);
        $this->assertSame(15000.0, $lastEntry['revenue']);
        $this->assertSame(2, $lastEntry['count']);
    }

    public function testGetKpisReturnsAllKeys(): void
    {
        $this->query->method('getSingleResult')
            ->willReturn(['cnt' => '0', 'total' => '0']);
        $this->query->method('getSingleScalarResult')
            ->willReturn('0');

        $conn = $this->createMock(Connection::class);
        $conn->method('fetchOne')->willReturn(false);
        $this->em->method('getConnection')->willReturn($conn);

        $kpis = $this->model->getKpis();

        $this->assertArrayHasKey('open_deals_count', $kpis);
        $this->assertArrayHasKey('open_deals_value', $kpis);
        $this->assertArrayHasKey('weighted_forecast', $kpis);
        $this->assertArrayHasKey('won_count', $kpis);
        $this->assertArrayHasKey('won_value', $kpis);
        $this->assertArrayHasKey('lost_count', $kpis);
        $this->assertArrayHasKey('lost_value', $kpis);
        $this->assertArrayHasKey('win_rate', $kpis);
        $this->assertArrayHasKey('avg_deal_size', $kpis);
        $this->assertArrayHasKey('avg_sales_cycle', $kpis);
    }

    public function testGetForecastDataReturnsAllSections(): void
    {
        $this->query->method('getSingleResult')
            ->willReturn(['cnt' => '0', 'total' => '0']);
        $this->query->method('getSingleScalarResult')
            ->willReturn('0');
        $this->query->method('getResult')
            ->willReturn([]);

        $conn = $this->createMock(Connection::class);
        $conn->method('fetchOne')->willReturn(false);
        $conn->method('fetchAllAssociative')->willReturn([]);
        $this->em->method('getConnection')->willReturn($conn);

        $data = $this->model->getForecastData();

        $this->assertArrayHasKey('kpis', $data);
        $this->assertArrayHasKey('funnel', $data);
        $this->assertArrayHasKey('revenue_over_time', $data);
        $this->assertArrayHasKey('deals_by_stage', $data);
    }

    public function testPipelineFilterIsApplied(): void
    {
        $this->qb->expects($this->atLeastOnce())
            ->method('andWhere')
            ->willReturnSelf();
        $this->qb->expects($this->atLeastOnce())
            ->method('setParameter')
            ->willReturnSelf();

        $this->query->method('getSingleResult')
            ->willReturn(['cnt' => '2', 'total' => '20000']);

        $result = $this->model->getOpenDeals(42);

        $this->assertSame(2, $result['count']);
    }

    public function testGetRecentActivity(): void
    {
        $conn      = $this->createMock(Connection::class);
        $stmt      = $this->createMock(Statement::class);
        $resultObj = $this->createMock(Result::class);

        $conn->method('prepare')->willReturn($stmt);
        $stmt->method('bindValue')->willReturnSelf();
        $stmt->method('executeQuery')->willReturn($resultObj);
        $resultObj->method('fetchAllAssociative')->willReturn([
            ['deal_name' => 'Big Deal', 'amount' => '50000.00', 'action' => 'won', 'date' => '2026-03-01 10:00:00'],
        ]);

        $this->em->method('getConnection')->willReturn($conn);

        $result = $this->model->getRecentActivity();

        $this->assertCount(1, $result);
        $this->assertSame('Big Deal', $result[0]['deal_name']);
        $this->assertSame('won', $result[0]['action']);
        $this->assertSame(50000.0, $result[0]['amount']);
    }
}
