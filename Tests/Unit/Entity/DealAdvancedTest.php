<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Entity;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use PHPUnit\Framework\TestCase;

class DealAdvancedTest extends TestCase
{
    private Deal $deal;

    protected function setUp(): void
    {
        $this->deal = new Deal();
    }

    public function testNewDealHasNullId(): void
    {
        $this->assertNull($this->deal->getId());
    }

    public function testNewDealHasNullDefaults(): void
    {
        $this->assertNull($this->deal->getName());
        $this->assertNull($this->deal->getDescription());
        $this->assertNull($this->deal->getAmount());
        $this->assertNull($this->deal->getCurrency());
        $this->assertNull($this->deal->getCloseDate());
        $this->assertNull($this->deal->getPipeline());
        $this->assertNull($this->deal->getStage());
        $this->assertNull($this->deal->getContact());
        $this->assertNull($this->deal->getCompany());
        $this->assertNull($this->deal->getOwner());
    }

    public function testSetDescription(): void
    {
        $this->deal->setDescription('A great deal');
        $this->assertSame('A great deal', $this->deal->getDescription());
    }

    public function testSetContact(): void
    {
        $contact = new Lead();
        $this->deal->setContact($contact);
        $this->assertSame($contact, $this->deal->getContact());
    }

    public function testSetNullContact(): void
    {
        $contact = new Lead();
        $this->deal->setContact($contact);
        $this->deal->setContact(null);
        $this->assertNull($this->deal->getContact());
    }

    public function testSetCompany(): void
    {
        $company = new Company();
        $this->deal->setCompany($company);
        $this->assertSame($company, $this->deal->getCompany());
    }

    public function testSetOwner(): void
    {
        $owner = new User();
        $this->deal->setOwner($owner);
        $this->assertSame($owner, $this->deal->getOwner());
    }

    public function testSetAndGetCategory(): void
    {
        $category = $this->createMock(\Mautic\CategoryBundle\Entity\Category::class);
        $category->method('getId')->willReturn(1);
        $this->deal->setCategory($category);
        $this->assertSame($category, $this->deal->getCategory());
    }

    public function testChangeTracking(): void
    {
        $this->deal->setName('Original');
        $this->deal->setName('Updated');
        $changes = $this->deal->getChanges();
        $this->assertArrayHasKey('name', $changes);
    }

    public function testCloneResetsId(): void
    {
        $deal = new Deal();
        $deal->setName('Clone me');
        $cloned = clone $deal;
        $this->assertNull($cloned->getId());
        $this->assertSame('Clone me', $cloned->getName());
    }

    public function testFluentSetters(): void
    {
        $result = $this->deal
            ->setName('Fluent Deal')
            ->setAmount('100.50')
            ->setCurrency('EUR')
            ->setDescription('Desc');

        $this->assertInstanceOf(Deal::class, $result);
        $this->assertSame('Fluent Deal', $this->deal->getName());
        $this->assertSame('100.50', $this->deal->getAmount());
        $this->assertSame('EUR', $this->deal->getCurrency());
    }

    public function testFullDealWorkflow(): void
    {
        $pipeline = new Pipeline();
        $pipeline->setName('Sales Pipeline');

        $stage = new Stage();
        $stage->setName('Qualification');
        $stage->setPipeline($pipeline);

        $this->deal->setName('Enterprise Contract');
        $this->deal->setAmount('250000.00');
        $this->deal->setCurrency('USD');
        $this->deal->setPipeline($pipeline);
        $this->deal->setStage($stage);
        $this->deal->setCloseDate(new \DateTime('2026-12-31'));

        $this->assertSame('Enterprise Contract', $this->deal->getName());
        $this->assertSame('250000.00', $this->deal->getAmount());
        $this->assertSame($pipeline, $this->deal->getPipeline());
        $this->assertSame($stage, $this->deal->getStage());
        $this->assertSame($pipeline, $this->deal->getStage()->getPipeline());
    }
}
