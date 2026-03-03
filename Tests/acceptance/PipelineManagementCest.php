<?php

declare(strict_types=1);

namespace MautomicCrmTests;

use MautomicCrmTests\Page\Acceptance\PipelinePage;
use MautomicCrmTests\Step\Acceptance\PipelineStep;

class PipelineManagementCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->login();
    }

    public function createPipelineWithStages(AcceptanceTester $I, PipelineStep $pipeline): void
    {
        $I->wantTo('Create a new pipeline with stages');

        $name = $I->uniqueName('Pipeline');
        $pipeline->createPipeline($name, 'Test pipeline description', [
            ['name' => 'Lead',       'order' => 1, 'probability' => 10, 'type' => 'open'],
            ['name' => 'Negotiation', 'order' => 2, 'probability' => 50, 'type' => 'open'],
            ['name' => 'Closed Won', 'order' => 3, 'probability' => 100, 'type' => 'won'],
        ]);

        $I->waitForText($name, 15);
        $I->takeNamedScreenshot('pipeline_created');
    }

    public function viewPipelineDetail(AcceptanceTester $I): void
    {
        $I->wantTo('View a pipeline detail page');

        $I->amOnPage(PipelinePage::$URL);
        $I->waitForPageLoad();
        $I->hideDebugToolbar();

        // Click first pipeline in list
        $I->click("//*[@id='pipelineTable']/tbody/tr[1]/td[2]//a");
        $I->waitForPageLoad();

        $I->seeElement('#toolbar');
        $I->takeNamedScreenshot('pipeline_detail');
    }

    public function editPipeline(AcceptanceTester $I, PipelineStep $pipeline): void
    {
        $I->wantTo('Edit an existing pipeline');

        $name = $I->uniqueName('PipeEdit');
        $pipeline->createPipeline($name, '', [
            ['name' => 'Stage A', 'order' => 1, 'probability' => 20, 'type' => 'open'],
        ]);

        $I->waitForText($name, 15);

        // Click edit button from detail view
        $I->click(PipelinePage::$editButton);
        $I->waitForElementVisible(PipelinePage::$nameField, 15);
        $I->hideDebugToolbar();

        $editedName = $name.' Edited';
        $I->fillField(PipelinePage::$nameField, $editedName);
        $I->clickSaveAndClose('pipeline');
        $I->waitForPageLoad();

        $I->waitForText($editedName, 15);
        $I->takeNamedScreenshot('pipeline_edited');
    }

    public function deletePipeline(AcceptanceTester $I, PipelineStep $pipeline): void
    {
        $I->wantTo('Delete a pipeline');

        $name = $I->uniqueName('PipeDel');
        $pipeline->createPipeline($name, '', [
            ['name' => 'Only Stage', 'order' => 1, 'probability' => 50, 'type' => 'open'],
        ]);

        // On detail page after creation
        $I->waitForText($name, 15);

        // Click options dropdown and delete
        $I->waitForElementClickable(PipelinePage::$dropDown, 10);
        $I->click(PipelinePage::$dropDown);
        $I->waitForElementClickable(PipelinePage::$deleteAction, 5);
        $I->click(PipelinePage::$deleteAction);
        $I->confirmDeletion();
        $I->waitForPageLoad();

        $I->waitForText('deleted', 15);
        $I->takeNamedScreenshot('pipeline_deleted');
    }
}
