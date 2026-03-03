<?php

declare(strict_types=1);

namespace MautomicCrmTests;

use MautomicCrmTests\Page\Acceptance\DealFieldPage;
use MautomicCrmTests\Step\Acceptance\DealFieldStep;

class DealFieldManagementCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->login();
    }

    public function createTextField(AcceptanceTester $I, DealFieldStep $dealField): void
    {
        $I->wantTo('Create a text deal field');

        $label = $I->uniqueName('TextField');
        $dealField->createDealField($label, 'text');

        $I->waitForText($label, 15);
        $I->takeNamedScreenshot('deal_field_text_created');
    }

    public function createSelectField(AcceptanceTester $I, DealFieldStep $dealField): void
    {
        $I->wantTo('Create a select deal field with options');

        $label = $I->uniqueName('SelectField');
        $dealField->createDealField($label, 'select', 'Option A|Option B|Option C');

        $I->waitForText($label, 15);
        $I->takeNamedScreenshot('deal_field_select_created');
    }

    public function listDealFields(AcceptanceTester $I): void
    {
        $I->wantTo('View the deal fields list');

        $I->amOnPage(DealFieldPage::$URL);
        $I->waitForPageLoad();
        $I->hideDebugToolbar();

        $I->seeElement('#dealFieldTable');
        $I->takeNamedScreenshot('deal_field_list');
    }

    public function editDealField(AcceptanceTester $I, DealFieldStep $dealField): void
    {
        $I->wantTo('Edit a deal field');

        $label = $I->uniqueName('FieldEdit');
        $dealField->createDealField($label, 'text');

        // Navigate to list
        $I->amOnPage(DealFieldPage::$URL);
        $I->waitForPageLoad();
        $I->hideDebugToolbar();

        // Click the first field to edit via dropdown
        $dealField->selectListDropdownOption(1, 1);
        $I->waitForElementVisible(DealFieldPage::$labelField, 15);
        $I->hideDebugToolbar();

        $editedLabel = $label.' Edited';
        $I->fillField(DealFieldPage::$labelField, $editedLabel);
        $I->clickSaveAndClose('deal_field');
        $I->waitForPageLoad();

        $I->waitForText($editedLabel, 15);
        $I->takeNamedScreenshot('deal_field_edited');
    }

    public function deleteDealField(AcceptanceTester $I, DealFieldStep $dealField): void
    {
        $I->wantTo('Delete a deal field');

        $label = $I->uniqueName('FieldDel');
        $dealField->createDealField($label, 'text');

        // Navigate to list
        $I->amOnPage(DealFieldPage::$URL);
        $I->waitForPageLoad();
        $I->hideDebugToolbar();

        // Use dropdown to delete
        $dealField->selectListDropdownOption(1, 2);
        $I->confirmDeletion();
        $I->waitForPageLoad();

        $I->waitForText('deleted', 15);
        $I->takeNamedScreenshot('deal_field_deleted');
    }
}
