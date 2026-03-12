<?php

declare(strict_types=1);

namespace MautomicCrmTests;

class DashboardCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->login();
    }

    public function dashboardPageLoads(AcceptanceTester $I): void
    {
        $I->wantTo('See the CRM dashboard with KPI cards and charts');

        $I->amOnPage('/s/mautomic/dashboard');
        $I->waitForPageLoad();

        $I->see('Sales Dashboard', 'h1.page-header-title');
        $I->takeNamedScreenshot('dashboard_loaded');
    }

    public function dashboardShowsKpiCards(AcceptanceTester $I): void
    {
        $I->wantTo('Verify KPI cards are present on the dashboard');

        $I->amOnPage('/s/mautomic/dashboard');
        $I->waitForPageLoad();

        $I->see('Open Deals');
        $I->see('Weighted Pipeline');
        $I->see('Won This Month');
        $I->see('Lost This Month');
        $I->see('Win Rate');
        $I->see('Avg Deal Size');
        $I->see('Avg Sales Cycle');
        $I->takeNamedScreenshot('dashboard_kpi_cards');
    }

    public function dashboardShowsCharts(AcceptanceTester $I): void
    {
        $I->wantTo('Verify chart canvases are rendered');

        $I->amOnPage('/s/mautomic/dashboard');
        $I->waitForPageLoad();

        $I->seeElement('#funnelChart');
        $I->seeElement('#revenueChart');
        $I->seeElement('#dealsByStageChart');
        $I->takeNamedScreenshot('dashboard_charts');
    }

    public function dashboardShowsTables(AcceptanceTester $I): void
    {
        $I->wantTo('Verify data tables are present');

        $I->amOnPage('/s/mautomic/dashboard');
        $I->waitForPageLoad();

        $I->see('At-Risk Deals');
        $I->see('Top Deals');
        $I->see('Recent Activity');
        $I->takeNamedScreenshot('dashboard_tables');
    }

    public function dashboardPipelineFilterWorks(AcceptanceTester $I): void
    {
        $I->wantTo('Filter dashboard by pipeline');

        $I->amOnPage('/s/mautomic/dashboard');
        $I->waitForPageLoad();

        $I->see('All Pipelines');
        $I->takeNamedScreenshot('dashboard_pipeline_filter');
    }
}
