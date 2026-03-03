<?php

declare(strict_types=1);

namespace MautomicCrmTests;

use MautomicCrmTests\Step\Acceptance\DealStep;
use MautomicCrmTests\Step\Acceptance\PipelineStep;

/**
 * Data seeding: creates a default pipeline and seed deal for other tests.
 * Runs first (alphabetical ordering).
 */
class A00_SetupCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->login();
    }

    public function createDefaultPipeline(AcceptanceTester $I, PipelineStep $pipeline): void
    {
        $I->wantTo('Create a default pipeline with 5 stages for E2E testing');

        $pipeline->createPipeline('E2E Default Pipeline', 'Auto-created for E2E tests', [
            ['name' => 'Qualified',    'order' => 1, 'probability' => 10, 'type' => 'open'],
            ['name' => 'Meeting',      'order' => 2, 'probability' => 30, 'type' => 'open'],
            ['name' => 'Proposal',     'order' => 3, 'probability' => 60, 'type' => 'open'],
            ['name' => 'Won',          'order' => 4, 'probability' => 100, 'type' => 'won'],
            ['name' => 'Lost',         'order' => 5, 'probability' => 0, 'type' => 'lost'],
        ]);

        $I->waitForText('E2E Default Pipeline', 15);
        $I->takeNamedScreenshot('setup_pipeline_created');
    }

    public function createSeedDeal(AcceptanceTester $I, DealStep $deal): void
    {
        $I->wantTo('Create a seed deal linked to the default pipeline');

        $deal->createDeal('E2E Seed Deal', 'E2E Default Pipeline', 'Qualified', '5000');

        $I->waitForText('E2E Seed Deal', 15);
        $I->takeNamedScreenshot('setup_seed_deal_created');
    }
}
