<?php

declare(strict_types=1);

namespace MautomicCrmTests;

use MautomicCrmTests\Page\Acceptance\DealPage;
use MautomicCrmTests\Step\Acceptance\DealStep;

class DealManagementCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->login();
    }

    public function createDeal(AcceptanceTester $I, DealStep $deal): void
    {
        $I->wantTo('Create a new deal');

        $name = $I->uniqueName('Deal');
        $deal->createDeal($name, 'E2E Default Pipeline', 'Qualified', '10000');

        $I->waitForText($name, 15);
        $I->takeNamedScreenshot('deal_created');
    }

    public function viewDealDetail(AcceptanceTester $I, DealStep $deal): void
    {
        $I->wantTo('View deal detail page with tasks and notes sections');

        $name = $I->uniqueName('DealView');
        $deal->createDeal($name, 'E2E Default Pipeline', 'Qualified');

        $I->waitForText($name, 15);

        // Verify deal detail sections exist
        $I->waitForText('Tasks', 15);
        $I->waitForText('Notes', 15);
        $I->takeNamedScreenshot('deal_detail_view');
    }

    public function editDeal(AcceptanceTester $I, DealStep $deal): void
    {
        $I->wantTo('Edit an existing deal');

        $name = $I->uniqueName('DealEdit');
        $deal->createDeal($name, 'E2E Default Pipeline', 'Qualified');

        // Now on detail view, click edit
        $I->waitForText($name, 15);
        $I->click(DealPage::$editButton);
        $I->waitForElementVisible(DealPage::$nameField, 15);
        $I->hideDebugToolbar();

        $editedName = $name.' Edited';
        $I->fillField(DealPage::$nameField, $editedName);
        $I->clickSaveAndClose('deal');
        $I->waitForPageLoad();

        $I->waitForText($editedName, 15);
        $I->takeNamedScreenshot('deal_edited');
    }

    public function quickStageChange(AcceptanceTester $I, DealStep $deal): void
    {
        $I->wantTo('Change deal stage via quick stage buttons');

        $name = $I->uniqueName('DealStage');
        $deal->createDeal($name, 'E2E Default Pipeline', 'Qualified');

        $I->waitForText($name, 15);
        $I->takeNamedScreenshot('deal_before_stage_change');

        // Click the "Meeting" stage button via XPath (form submit button containing text)
        $I->click("//form[contains(@action, '/stage')]//button[contains(., 'Meeting')]");
        $I->waitForPageLoad();

        $I->takeNamedScreenshot('deal_after_stage_change');
    }

    public function deleteDeal(AcceptanceTester $I, DealStep $deal): void
    {
        $I->wantTo('Delete a deal');

        $name = $I->uniqueName('DealDel');
        $deal->createDeal($name, 'E2E Default Pipeline', 'Qualified');

        $I->waitForText($name, 15);

        // Click options dropdown and delete
        $I->waitForElementClickable(DealPage::$dropDown, 10);
        $I->click(DealPage::$dropDown);
        $I->waitForElementClickable(DealPage::$deleteAction, 5);
        $I->click(DealPage::$deleteAction);
        $I->confirmDeletion();
        $I->waitForPageLoad();

        $I->waitForText('deleted', 15);
        $I->takeNamedScreenshot('deal_deleted');
    }

    public function createDealWithCustomFieldValues(AcceptanceTester $I, DealStep $deal): void
    {
        $I->wantTo('Create a deal and verify custom fields appear on form');

        $name = $I->uniqueName('DealCF');

        $I->amOnPage(DealPage::$newURL);
        $I->waitForElementVisible(DealPage::$nameField, 15);
        $I->hideDebugToolbar();

        // Verify the standard form fields are present (Chosen containers, not hidden native selects)
        $I->seeElement(DealPage::$nameField);
        $I->seeElement('#deal_pipeline_chosen');
        $I->seeElement('#deal_stage_chosen');

        // Fill form and save
        $I->fillField(DealPage::$nameField, $name);
        $I->selectChosenOption(DealPage::$pipelineField, 'E2E Default Pipeline');
        $I->wait(2);
        $I->selectChosenOption(DealPage::$stageField, 'Proposal');
        $I->clickSaveAndClose('deal');
        $I->waitForPageLoad();

        $I->waitForText($name, 15);
        $I->takeNamedScreenshot('deal_custom_fields_form');
    }
}
