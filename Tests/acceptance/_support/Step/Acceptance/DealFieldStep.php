<?php

declare(strict_types=1);

namespace MautomicCrmTests\Step\Acceptance;

use MautomicCrmTests\AcceptanceTester;
use MautomicCrmTests\Page\Acceptance\DealFieldPage;

class DealFieldStep extends AcceptanceTester
{
    /**
     * Create a deal field via the admin form.
     */
    public function createDealField(string $label, string $type = 'text', string $properties = ''): void
    {
        $I = $this;
        $I->amOnPage(DealFieldPage::$newURL);
        $I->waitForElementVisible(DealFieldPage::$labelField, 15);
        $I->hideDebugToolbar();
        $I->fillField(DealFieldPage::$labelField, $label);

        // Generate alias from label (lowercase, underscores)
        $alias = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $label));
        $I->fillField(DealFieldPage::$aliasField, $alias);

        $I->selectChosenOption(DealFieldPage::$typeField, $type);

        if ('' !== $properties) {
            $I->fillField('#deal_field_properties', $properties);
        }

        $I->clickSaveAndClose('deal_field');
        $I->waitForPageLoad();
    }

    /**
     * Grab a deal field label from the list at given position (1-indexed).
     */
    public function grabDealFieldLabelFromList(int $position): string
    {
        $I     = $this;
        $xpath = "//*[@id='dealFieldTable']/tbody/tr[{$position}]/td[2]//a";

        return $I->grabTextFrom($xpath);
    }

    /**
     * Click dropdown option on a deal field list row.
     */
    public function selectListDropdownOption(int $rowPosition, int $optionPosition): void
    {
        $I = $this;
        $I->click("//*[@id='dealFieldTable']/tbody/tr[{$rowPosition}]/td[1]//button");
        $I->waitForElementClickable(
            "//*[@id='dealFieldTable']/tbody/tr[{$rowPosition}]/td[1]//ul/li[{$optionPosition}]/a",
            10
        );
        $I->click("//*[@id='dealFieldTable']/tbody/tr[{$rowPosition}]/td[1]//ul/li[{$optionPosition}]/a");
    }
}
