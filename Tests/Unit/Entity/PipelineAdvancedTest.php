<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Entity;

use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use PHPUnit\Framework\TestCase;

class PipelineAdvancedTest extends TestCase
{
    private Pipeline $pipeline;

    protected function setUp(): void
    {
        $this->pipeline = new Pipeline();
    }

    public function testNewPipelineDefaults(): void
    {
        $this->assertNull($this->pipeline->getId());
        $this->assertNull($this->pipeline->getName());
        $this->assertNull($this->pipeline->getDescription());
        $this->assertFalse($this->pipeline->getIsDefault());
        $this->assertCount(0, $this->pipeline->getStages());
    }

    public function testAddStage(): void
    {
        $stage = new Stage();
        $stage->setName('Prospecting');

        $this->pipeline->addStage($stage);

        $this->assertCount(1, $this->pipeline->getStages());
        $this->assertSame($this->pipeline, $stage->getPipeline());
    }

    public function testAddDuplicateStageIsIgnored(): void
    {
        $stage = new Stage();
        $stage->setName('Prospecting');

        $this->pipeline->addStage($stage);
        $this->pipeline->addStage($stage);

        $this->assertCount(1, $this->pipeline->getStages());
    }

    public function testRemoveStage(): void
    {
        $stage = new Stage();
        $stage->setName('Prospecting');

        $this->pipeline->addStage($stage);
        $this->pipeline->removeStage($stage);

        $this->assertCount(0, $this->pipeline->getStages());
    }

    public function testMultipleStagesOrdering(): void
    {
        $stage1 = new Stage();
        $stage1->setName('Prospecting');
        $stage1->setOrder(1);

        $stage2 = new Stage();
        $stage2->setName('Qualification');
        $stage2->setOrder(2);

        $stage3 = new Stage();
        $stage3->setName('Closed Won');
        $stage3->setOrder(3);
        $stage3->setType('won');

        $this->pipeline->addStage($stage1);
        $this->pipeline->addStage($stage2);
        $this->pipeline->addStage($stage3);

        $this->assertCount(3, $this->pipeline->getStages());
    }

    public function testCloneResetsPipelineId(): void
    {
        $this->pipeline->setName('Original');
        $cloned = clone $this->pipeline;
        $this->assertNull($cloned->getId());
        $this->assertSame('Original', $cloned->getName());
    }

    public function testChangeTrackingOnIsDefault(): void
    {
        $this->pipeline->setIsDefault(true);
        $changes = $this->pipeline->getChanges();
        $this->assertArrayHasKey('isDefault', $changes);
    }
}
