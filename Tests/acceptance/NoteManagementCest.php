<?php

declare(strict_types=1);

namespace MautomicCrmTests;

use MautomicCrmTests\Page\Acceptance\DealPage;
use MautomicCrmTests\Page\Acceptance\NotePage;
use MautomicCrmTests\Step\Acceptance\NoteStep;

class NoteManagementCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->login();
    }

    public function createNoteFromDealDetail(AcceptanceTester $I, NoteStep $note): void
    {
        $I->wantTo('Create a note from the deal detail page');

        // Navigate to the seed deal detail
        $I->amOnPage(DealPage::$URL);
        $I->waitForPageLoad();
        $I->hideDebugToolbar();

        // Click the seed deal
        $I->click('E2E Seed Deal');
        $I->waitForText('E2E Seed Deal', 15);

        // Click "Add Note" button from deal detail
        $I->click('a[href*="/notes/new"]');
        $I->waitForElementVisible(NotePage::$textField, 15);
        $I->hideDebugToolbar();

        $noteText = $I->uniqueName('Note').' - Created from deal detail';
        $I->fillField(NotePage::$textField, $noteText);
        $I->selectChosenOption(NotePage::$typeField, 'call');
        $I->clickSaveAndClose('note');
        $I->waitForPageLoad();

        // Should return to deal detail and see the note
        $I->waitForText($noteText, 15);
        $I->takeNamedScreenshot('note_created_from_deal');
    }

    public function editNote(AcceptanceTester $I, NoteStep $note): void
    {
        $I->wantTo('Edit an existing note');

        // Create a note first
        $noteText = $I->uniqueName('NoteEdit').' - Original';
        $note->createNote($noteText, 'general', 'E2E Seed Deal');

        // Navigate to the deal detail to find the note
        $I->amOnPage(DealPage::$URL);
        $I->waitForPageLoad();
        $I->hideDebugToolbar();
        $I->click('E2E Seed Deal');
        $I->waitForText('E2E Seed Deal', 15);

        // Click edit button on the first note (pencil icon)
        $I->click('a[href*="/notes/edit/"]');
        $I->waitForElementVisible(NotePage::$textField, 15);
        $I->hideDebugToolbar();

        $editedText = $noteText.' - Edited';
        $I->fillField(NotePage::$textField, $editedText);
        $I->clickSaveAndClose('note');
        $I->waitForPageLoad();

        $I->waitForText($editedText, 15);
        $I->takeNamedScreenshot('note_edited');
    }

    public function deleteNote(AcceptanceTester $I, NoteStep $note): void
    {
        $I->wantTo('Delete a note');

        // Create a note to delete
        $noteText = $I->uniqueName('NoteDel').' - Will be deleted';
        $note->createNote($noteText, 'meeting', 'E2E Seed Deal');

        // Navigate to the deal detail
        $I->amOnPage(DealPage::$URL);
        $I->waitForPageLoad();
        $I->hideDebugToolbar();
        $I->click('E2E Seed Deal');
        $I->waitForText('E2E Seed Deal', 15);
        $I->waitForText($noteText, 15);

        // Find the specific note panel and click its delete button via XPath
        $I->click("//div[contains(@class, 'panel-body')][contains(., '{$noteText}')]//a[contains(@class, 'btn-danger')]");
        $I->confirmDeletion();
        $I->wait(3); // Wait for AJAX delete to complete

        // Reload the deal detail page to verify deletion
        $I->amOnPage(DealPage::$URL);
        $I->waitForPageLoad();
        $I->hideDebugToolbar();
        $I->click('E2E Seed Deal');
        $I->waitForText('E2E Seed Deal', 15);

        $I->dontSee($noteText);
        $I->takeNamedScreenshot('note_deleted');
    }
}
