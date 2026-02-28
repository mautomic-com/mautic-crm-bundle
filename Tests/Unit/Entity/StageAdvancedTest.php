<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Entity;

use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use PHPUnit\Framework\TestCase;

class StageAdvancedTest extends TestCase
{
    private Stage $stage;

    protected function setUp(): void
    {
        $this->stage = new Stage();
    }

    public function testNewStageDefaults(): void
    {
        $this->assertNull($this->stage->getId());
        $this->assertNull($this->stage->getName());
        $this->assertSame(0, $this->stage->getOrder());
        $this->assertSame(0, $this->stage->getProbability());
        $this->assertSame('open', $this->stage->getType());
        $this->assertNull($this->stage->getPipeline());
    }

    public function testAllStageTypes(): void
    {
        foreach (['open', 'won', 'lost'] as $type) {
            $this->stage->setType($type);
            $this->assertSame($type, $this->stage->getType());
        }
    }

    public function testProbabilityRange(): void
    {
        $this->stage->setProbability(0);
        $this->assertSame(0, $this->stage->getProbability());

        $this->stage->setProbability(50);
        $this->assertSame(50, $this->stage->getProbability());

        $this->stage->setProbability(100);
        $this->assertSame(100, $this->stage->getProbability());
    }

    public function testStagePipelineRelationship(): void
    {
        $pipeline = new Pipeline();
        $pipeline->setName('Sales');

        $this->stage->setPipeline($pipeline);
        $this->assertSame($pipeline, $this->stage->getPipeline());
    }

    public function testCloneResetsId(): void
    {
        $this->stage->setName('Qualification');
        $cloned = clone $this->stage;
        $this->assertNull($cloned->getId());
        $this->assertSame('Qualification', $cloned->getName());
    }

    public function testChangeTracking(): void
    {
        $this->stage->setName('Original');
        $this->stage->setName('Changed');
        $changes = $this->stage->getChanges();
        $this->assertArrayHasKey('name', $changes);
    }

    public function testTypicalSalesPipelineStages(): void
    {
        $pipeline = new Pipeline();
        $pipeline->setName('Default Sales');

        $stages = [
            ['name' => 'Prospecting', 'order' => 1, 'probability' => 10, 'type' => 'open'],
            ['name' => 'Qualification', 'order' => 2, 'probability' => 25, 'type' => 'open'],
            ['name' => 'Proposal', 'order' => 3, 'probability' => 50, 'type' => 'open'],
            ['name' => 'Negotiation', 'order' => 4, 'probability' => 75, 'type' => 'open'],
            ['name' => 'Closed Won', 'order' => 5, 'probability' => 100, 'type' => 'won'],
            ['name' => 'Closed Lost', 'order' => 6, 'probability' => 0, 'type' => 'lost'],
        ];

        foreach ($stages as $data) {
            $stage = new Stage();
            $stage->setName($data['name']);
            $stage->setOrder($data['order']);
            $stage->setProbability($data['probability']);
            $stage->setType($data['type']);
            $pipeline->addStage($stage);
        }

        $this->assertCount(6, $pipeline->getStages());
    }
}
