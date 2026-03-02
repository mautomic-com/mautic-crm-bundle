<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Entity;

use MauticPlugin\MautomicCrmBundle\Entity\DealField;
use PHPUnit\Framework\TestCase;

class DealFieldTest extends TestCase
{
    public function testGetSetLabel(): void
    {
        $field = new DealField();
        $field->setLabel('Contract Number');
        $this->assertSame('Contract Number', $field->getLabel());
    }

    public function testGetSetAlias(): void
    {
        $field = new DealField();
        $field->setAlias('contract_number');
        $this->assertSame('contract_number', $field->getAlias());
    }

    public function testGetSetType(): void
    {
        $field = new DealField();
        $field->setType('text');
        $this->assertSame('text', $field->getType());
    }

    public function testGetSetIsRequired(): void
    {
        $field = new DealField();
        $this->assertFalse($field->getIsRequired());
        $field->setIsRequired(true);
        $this->assertTrue($field->getIsRequired());
    }

    public function testGetSetFieldGroup(): void
    {
        $field = new DealField();
        $this->assertNull($field->getFieldGroup());
        $field->setFieldGroup('Contract Details');
        $this->assertSame('Contract Details', $field->getFieldGroup());
    }

    public function testGetSetFieldOrder(): void
    {
        $field = new DealField();
        $this->assertSame(0, $field->getFieldOrder());
        $field->setFieldOrder(5);
        $this->assertSame(5, $field->getFieldOrder());
    }

    public function testFieldOrderNullFallback(): void
    {
        $field = new DealField();
        $field->setFieldOrder(null);
        $this->assertSame(0, $field->getFieldOrder());
    }

    public function testGetSetProperties(): void
    {
        $field = new DealField();
        $this->assertNull($field->getProperties());
        $field->setProperties('Option A|Option B|Option C');
        $this->assertSame('Option A|Option B|Option C', $field->getProperties());
    }

    public function testDefaultValues(): void
    {
        $field = new DealField();
        $this->assertNull($field->getId());
        $this->assertNull($field->getLabel());
        $this->assertNull($field->getAlias());
        $this->assertNull($field->getType());
        $this->assertFalse($field->getIsRequired());
        $this->assertNull($field->getFieldGroup());
        $this->assertSame(0, $field->getFieldOrder());
        $this->assertNull($field->getProperties());
    }

    public function testGetNameReturnsLabel(): void
    {
        $field = new DealField();
        $field->setLabel('My Field');
        $this->assertSame('My Field', $field->getName());
    }

    public function testSupportedTypes(): void
    {
        $this->assertCount(6, DealField::SUPPORTED_TYPES);
        $this->assertContains('text', DealField::SUPPORTED_TYPES);
        $this->assertContains('boolean', DealField::SUPPORTED_TYPES);
    }
}
