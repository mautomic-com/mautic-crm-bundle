<?php

declare(strict_types=1);

namespace MautomicCrmTests\Step\Acceptance;

use MautomicCrmTests\AcceptanceTester;
use MautomicCrmTests\Page\Acceptance\TaskPage;

class TaskStep extends AcceptanceTester
{
    /**
     * Create a task via the new task form.
     */
    public function createTask(string $title, string $description = '', ?string $dealName = null): void
    {
        $I = $this;
        $I->amOnPage(TaskPage::$newURL);
        $I->waitForElementVisible(TaskPage::$titleField, 15);
        $I->hideDebugToolbar();
        $I->fillField(TaskPage::$titleField, $title);

        if ('' !== $description) {
            $I->fillField(TaskPage::$descriptionField, $description);
        }

        if (null !== $dealName) {
            $I->selectChosenOption(TaskPage::$dealField, $dealName);
        }

        $I->clickSaveAndClose('task');
        $I->waitForPageLoad();
    }

    /**
     * Grab a task title from the list at given position (1-indexed).
     */
    public function grabTaskNameFromList(int $position): string
    {
        $I     = $this;
        $xpath = "//*[@id='taskTable']/tbody/tr[{$position}]/td[2]//a";

        return $I->grabTextFrom($xpath);
    }

    /**
     * Click dropdown option on a task list row.
     */
    public function selectListDropdownOption(int $rowPosition, int $optionPosition): void
    {
        $I = $this;
        $I->click("//*[@id='taskTable']/tbody/tr[{$rowPosition}]/td[1]//button");
        $I->waitForElementClickable(
            "//*[@id='taskTable']/tbody/tr[{$rowPosition}]/td[1]//ul/li[{$optionPosition}]/a",
            10
        );
        $I->click("//*[@id='taskTable']/tbody/tr[{$rowPosition}]/td[1]//ul/li[{$optionPosition}]/a");
    }
}
