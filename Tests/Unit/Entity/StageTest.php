<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Entity;

use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use PHPUnit\Framework\TestCase;

class StageTest extends TestCase
{
    public function testGetSetName(): void
    {
        $stage = new Stage();
        $stage->setName('Qualification');
        $this->assertSame('Qualification', $stage->getName());
    }

    public function testGetSetOrder(): void
    {
        $stage = new Stage();
        $stage->setOrder(3);
        $this->assertSame(3, $stage->getOrder());
    }

    public function testGetSetProbability(): void
    {
        $stage = new Stage();
        $stage->setProbability(75);
        $this->assertSame(75, $stage->getProbability());
    }

    public function testDefaultType(): void
    {
        $stage = new Stage();
        $this->assertSame('open', $stage->getType());
    }

    public function testGetSetPipeline(): void
    {
        $stage    = new Stage();
        $pipeline = new Pipeline();
        $pipeline->setName('Test Pipeline');
        $stage->setPipeline($pipeline);
        $this->assertSame($pipeline, $stage->getPipeline());
    }
}
