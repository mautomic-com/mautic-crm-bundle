<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Entity;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\Note;
use PHPUnit\Framework\TestCase;

class NoteAdvancedTest extends TestCase
{
    private Note $note;

    protected function setUp(): void
    {
        $this->note = new Note();
    }

    public function testNewNoteDefaults(): void
    {
        $this->assertNull($this->note->getId());
        $this->assertNull($this->note->getText());
        $this->assertSame('general', $this->note->getType());
        $this->assertNull($this->note->getDeal());
        $this->assertNull($this->note->getContact());
    }

    public function testAllNoteTypes(): void
    {
        foreach (['general', 'call', 'meeting', 'email'] as $type) {
            $this->note->setType($type);
            $this->assertSame($type, $this->note->getType());
        }
    }

    public function testGetNameTruncatesLongText(): void
    {
        $longText = str_repeat('A', 100);
        $this->note->setText($longText);
        $name = $this->note->getName();
        $this->assertSame(50, mb_strlen($name));
    }

    public function testGetNameReturnsFullShortText(): void
    {
        $this->note->setText('Short note');
        $this->assertSame('Short note', $this->note->getName());
    }

    public function testGetNameWithNullText(): void
    {
        $this->assertSame('', $this->note->getName());
    }

    public function testLinkToDeal(): void
    {
        $deal = new Deal();
        $deal->setName('Deal');
        $this->note->setDeal($deal);
        $this->assertSame($deal, $this->note->getDeal());
    }

    public function testLinkToContact(): void
    {
        $contact = new Lead();
        $this->note->setContact($contact);
        $this->assertSame($contact, $this->note->getContact());
    }

    public function testCloneResetsId(): void
    {
        $this->note->setText('Clone me');
        $cloned = clone $this->note;
        $this->assertNull($cloned->getId());
        $this->assertSame('Clone me', $cloned->getText());
    }

    public function testChangeTracking(): void
    {
        $this->note->setText('Initial');
        $this->note->setText('Updated');
        $changes = $this->note->getChanges();
        $this->assertArrayHasKey('text', $changes);
    }
}
