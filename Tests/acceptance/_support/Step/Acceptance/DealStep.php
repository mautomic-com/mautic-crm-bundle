<?php

declare(strict_types=1);

namespace MautomicCrmTests\Step\Acceptance;

use MautomicCrmTests\AcceptanceTester;
use MautomicCrmTests\Page\Acceptance\DealPage;

class DealStep extends AcceptanceTester
{
    /**
     * Create a deal via the new deal form.
     */
    public function createDeal(string $name, string $pipelineName, string $stageName, string $amount = ''): void
    {
        $I = $this;
        $I->amOnPage(DealPage::$newURL);
        $I->waitForElementVisible(DealPage::$nameField, 15);
        $I->hideDebugToolbar();
        $I->fillField(DealPage::$nameField, $name);

        $I->selectChosenOption(DealPage::$pipelineField, $pipelineName);
        $I->wait(2); // Wait for pipeline change to reload stages via AJAX
        $I->selectChosenOption(DealPage::$stageField, $stageName);

        if ('' !== $amount) {
            $I->fillField(DealPage::$amountField, $amount);
        }

        $I->clickSaveAndClose('deal');
        $I->waitForPageLoad();
    }

    /**
     * Grab a deal name from the list at given position (1-indexed).
     */
    public function grabDealNameFromList(int $position): string
    {
        $I     = $this;
        $xpath = "//*[@id='dealTable']/tbody/tr[{$position}]/td[2]//a";

        return $I->grabTextFrom($xpath);
    }

    /**
     * Click dropdown option on a deal list row.
     */
    public function selectListDropdownOption(int $rowPosition, int $optionPosition): void
    {
        $I = $this;
        $I->click("//*[@id='dealTable']/tbody/tr[{$rowPosition}]/td[1]//button");
        $I->waitForElementClickable(
            "//*[@id='dealTable']/tbody/tr[{$rowPosition}]/td[1]//ul/li[{$optionPosition}]/a",
            10
        );
        $I->click("//*[@id='dealTable']/tbody/tr[{$rowPosition}]/td[1]//ul/li[{$optionPosition}]/a");
    }

    /**
     * Quick stage change via detail view dropdown (if implemented).
     */
    public function quickStageChange(int $dealId, string $newStageName): void
    {
        $I = $this;
        $I->amOnPage(DealPage::viewURL($dealId));
        $I->waitForPageLoad();

        // Look for a stage selector or quick-change button on the deal detail
        $I->selectOption('#deal-stage-quick-change', $newStageName);
        $I->wait(2);
    }
}
