<?php

declare(strict_types=1);

namespace MautomicCrmTests;

use MautomicCrmTests\Page\Acceptance\DealFieldPage;
use MautomicCrmTests\Page\Acceptance\DealPage;
use MautomicCrmTests\Page\Acceptance\PipelinePage;
use MautomicCrmTests\Page\Acceptance\TaskPage;

class CrmNavigationCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->login();
    }

    public function navigateToCrmMenuSection(AcceptanceTester $I): void
    {
        $I->wantTo('See the CRM section in the main menu');

        $I->amOnPage('/s/dashboard');
        $I->waitForPageLoad();
        $I->hideDebugToolbar();

        // The CRM menu section should exist
        $I->seeElement('#mautomic_crm_root');
        $I->takeNamedScreenshot('nav_crm_menu');
    }

    public function navigateToPipelines(AcceptanceTester $I): void
    {
        $I->wantTo('Navigate to pipelines via URL');

        $I->amOnPage(PipelinePage::$URL);
        $I->waitForPageLoad();
        $I->hideDebugToolbar();

        $I->see('Pipelines', 'h1.page-header-title');
        $I->takeNamedScreenshot('nav_pipelines');
    }

    public function navigateToDeals(AcceptanceTester $I): void
    {
        $I->wantTo('Navigate to deals via URL');

        $I->amOnPage(DealPage::$URL);
        $I->waitForPageLoad();
        $I->hideDebugToolbar();

        $I->see('Deals', 'h1.page-header-title');
        $I->takeNamedScreenshot('nav_deals');
    }

    public function navigateToTasks(AcceptanceTester $I): void
    {
        $I->wantTo('Navigate to tasks via URL');

        $I->amOnPage(TaskPage::$URL);
        $I->waitForPageLoad();
        $I->hideDebugToolbar();

        $I->see('Tasks', 'h1.page-header-title');
        $I->takeNamedScreenshot('nav_tasks');
    }

    public function navigateToDashboard(AcceptanceTester $I): void
    {
        $I->wantTo('Navigate to CRM dashboard via URL');

        $I->amOnPage('/s/mautomic/dashboard');
        $I->waitForPageLoad();
        $I->hideDebugToolbar();

        $I->see('CRM Dashboard', 'h1.page-header-title');
        $I->takeNamedScreenshot('nav_dashboard');
    }

    public function navigateToDealFieldsViaAdmin(AcceptanceTester $I): void
    {
        $I->wantTo('Navigate to deal fields via admin URL');

        $I->amOnPage(DealFieldPage::$URL);
        $I->waitForPageLoad();
        $I->hideDebugToolbar();

        $I->see('Deal Fields', 'h1.page-header-title');
        $I->takeNamedScreenshot('nav_deal_fields');
    }

    public function crossEntityLinksFromDealDetail(AcceptanceTester $I): void
    {
        $I->wantTo('Verify cross-entity links from deal detail page');

        // Go to the seed deal detail
        $I->amOnPage(DealPage::$URL);
        $I->waitForPageLoad();
        $I->hideDebugToolbar();

        $I->click('E2E Seed Deal');
        $I->waitForPageLoad();

        // Verify we see the pipeline name on the detail page
        $I->see('E2E Default Pipeline');

        // Verify tasks section header exists
        $I->see('Tasks');

        // Verify notes section header exists
        $I->see('Notes');

        // Verify there's an "Add Note" link
        $I->seeElement('a[href*="/notes/new"]');

        $I->takeNamedScreenshot('nav_deal_cross_entity_links');
    }
}
