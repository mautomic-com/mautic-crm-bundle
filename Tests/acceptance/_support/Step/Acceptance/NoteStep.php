<?php

declare(strict_types=1);

namespace MautomicCrmTests\Step\Acceptance;

use MautomicCrmTests\AcceptanceTester;
use MautomicCrmTests\Page\Acceptance\NotePage;

class NoteStep extends AcceptanceTester
{
    /**
     * Create a note via the new note form, optionally linked to a deal.
     */
    public function createNote(string $text, string $type = 'general', ?string $dealName = null): void
    {
        $I = $this;
        $I->amOnPage(NotePage::$newURL);
        $I->waitForElementVisible(NotePage::$textField, 15);
        $I->hideDebugToolbar();
        $I->fillField(NotePage::$textField, $text);
        $I->selectChosenOption(NotePage::$typeField, $type);

        if (null !== $dealName) {
            $I->selectChosenOption(NotePage::$dealField, $dealName);
        }

        $I->clickSaveAndClose('note');
        $I->waitForPageLoad();
    }
}
