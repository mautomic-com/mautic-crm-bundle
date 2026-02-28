<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Entity;

use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use PHPUnit\Framework\TestCase;

class DealTest extends TestCase
{
    public function testGetSetName(): void
    {
        $deal = new Deal();
        $deal->setName('Big Deal');
        $this->assertSame('Big Deal', $deal->getName());
    }

    public function testGetSetAmount(): void
    {
        $deal = new Deal();
        $deal->setAmount('50000.00');
        $this->assertSame('50000.00', $deal->getAmount());
    }

    public function testGetSetCurrency(): void
    {
        $deal = new Deal();
        $deal->setCurrency('USD');
        $this->assertSame('USD', $deal->getCurrency());
    }

    public function testGetSetCloseDate(): void
    {
        $deal = new Deal();
        $date = new \DateTime('2026-06-15');
        $deal->setCloseDate($date);
        $this->assertSame($date, $deal->getCloseDate());
    }

    public function testGetSetPipeline(): void
    {
        $deal     = new Deal();
        $pipeline = new Pipeline();
        $pipeline->setName('Test');
        $deal->setPipeline($pipeline);
        $this->assertSame($pipeline, $deal->getPipeline());
    }

    public function testGetSetStage(): void
    {
        $deal  = new Deal();
        $stage = new Stage();
        $stage->setName('Qualification');
        $deal->setStage($stage);
        $this->assertSame($stage, $deal->getStage());
    }
}
