<?php

declare(strict_types=1);

namespace MautomicCrmTests\Step\Acceptance;

use MautomicCrmTests\AcceptanceTester;
use MautomicCrmTests\Page\Acceptance\PipelinePage;

class PipelineStep extends AcceptanceTester
{
    /**
     * Create a pipeline with given name and optional stages.
     *
     * @param array<int, array{name: string, order: int, probability: int, type?: string}> $stages
     */
    public function createPipeline(string $name, string $description = '', array $stages = []): void
    {
        $I = $this;
        $I->amOnPage(PipelinePage::$newURL);
        $I->waitForElementVisible(PipelinePage::$nameField, 15);
        $I->hideDebugToolbar();
        $I->fillField(PipelinePage::$nameField, $name);

        if ('' !== $description) {
            $I->fillField(PipelinePage::$descriptionField, $description);
        }

        foreach ($stages as $index => $stage) {
            $I->addStageViaJS($index, $stage['name'], $stage['order'], $stage['probability'], $stage['type'] ?? 'open');
        }

        $I->clickSaveAndClose('pipeline');
        $I->waitForPageLoad();
    }

    /**
     * Add a stage row by triggering the JS prototype and filling fields.
     */
    public function addStageViaJS(int $index, string $name, int $order, int $probability, string $type = 'open'): void
    {
        $I = $this;
        $I->executeJS('MautomicCrm.addStage();');
        $I->wait(1);

        // Stages use CollectionType, the field IDs are pipeline_stages_{index}_{field}
        $stageCount = $I->executeJS('return document.querySelectorAll("#stages-list .stage-row").length;');
        $jsIndex    = $stageCount - 1;

        $I->fillField("input[id$='stages_{$jsIndex}_name']", $name);
        $I->fillField("input[id$='stages_{$jsIndex}_order']", (string) $order);
        $I->fillField("input[id$='stages_{$jsIndex}_probability']", (string) $probability);
        $I->selectOption("select[id$='stages_{$jsIndex}_type']", $type);
    }

    /**
     * Grab a pipeline name from the list at given position (1-indexed).
     */
    public function grabPipelineNameFromList(int $position): string
    {
        $I     = $this;
        $xpath = "//*[@id='pipelineTable']/tbody/tr[{$position}]/td[2]//a";

        return $I->grabTextFrom($xpath);
    }

    /**
     * Click dropdown option on a list row.
     */
    public function selectListDropdownOption(int $rowPosition, int $optionPosition): void
    {
        $I = $this;
        $I->click("//*[@id='pipelineTable']/tbody/tr[{$rowPosition}]/td[1]//button");
        $I->waitForElementClickable(
            "//*[@id='pipelineTable']/tbody/tr[{$rowPosition}]/td[1]//ul/li[{$optionPosition}]/a",
            10
        );
        $I->click("//*[@id='pipelineTable']/tbody/tr[{$rowPosition}]/td[1]//ul/li[{$optionPosition}]/a");
    }
}
