<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Entity;

use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\DealField;
use MauticPlugin\MautomicCrmBundle\Entity\DealFieldValue;
use PHPUnit\Framework\TestCase;

class DealFieldValueTest extends TestCase
{
    public function testGetSetDeal(): void
    {
        $value = new DealFieldValue();
        $deal  = new Deal();
        $deal->setName('Test Deal');

        $value->setDeal($deal);
        $this->assertSame($deal, $value->getDeal());
    }

    public function testGetSetField(): void
    {
        $value = new DealFieldValue();
        $field = new DealField();
        $field->setLabel('Contract');

        $value->setField($field);
        $this->assertSame($field, $value->getField());
    }

    public function testGetSetValue(): void
    {
        $value = new DealFieldValue();
        $this->assertNull($value->getValue());

        $value->setValue('C-12345');
        $this->assertSame('C-12345', $value->getValue());
    }

    public function testDefaultValues(): void
    {
        $value = new DealFieldValue();
        $this->assertNull($value->getId());
        $this->assertNull($value->getDeal());
        $this->assertNull($value->getField());
        $this->assertNull($value->getValue());
    }

    public function testNullValue(): void
    {
        $value = new DealFieldValue();
        $value->setValue('something');
        $value->setValue(null);
        $this->assertNull($value->getValue());
    }
}
