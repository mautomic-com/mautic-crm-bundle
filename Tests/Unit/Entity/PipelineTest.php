<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Entity;

use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use PHPUnit\Framework\TestCase;

class PipelineTest extends TestCase
{
    public function testGetSetName(): void
    {
        $pipeline = new Pipeline();
        $pipeline->setName('Enterprise Sales');
        $this->assertSame('Enterprise Sales', $pipeline->getName());
    }

    public function testGetSetDescription(): void
    {
        $pipeline = new Pipeline();
        $pipeline->setDescription('For large accounts');
        $this->assertSame('For large accounts', $pipeline->getDescription());
    }

    public function testGetSetIsDefault(): void
    {
        $pipeline = new Pipeline();
        $this->assertFalse($pipeline->getIsDefault());

        $pipeline->setIsDefault(true);
        $this->assertTrue($pipeline->getIsDefault());
    }

    public function testStagesCollection(): void
    {
        $pipeline = new Pipeline();
        $this->assertCount(0, $pipeline->getStages());
    }
}
