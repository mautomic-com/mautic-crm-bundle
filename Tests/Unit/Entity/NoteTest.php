<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Entity;

use MauticPlugin\MautomicCrmBundle\Entity\Note;
use PHPUnit\Framework\TestCase;

class NoteTest extends TestCase
{
    public function testGetSetText(): void
    {
        $note = new Note();
        $note->setText('Called the client, discussed pricing.');
        $this->assertSame('Called the client, discussed pricing.', $note->getText());
    }

    public function testDefaultType(): void
    {
        $note = new Note();
        $this->assertSame('general', $note->getType());
    }

    public function testGetSetType(): void
    {
        $note = new Note();
        $note->setType('call');
        $this->assertSame('call', $note->getType());
    }

    public function testGetNameReturnsTruncatedText(): void
    {
        $note = new Note();
        $note->setText('Short note');
        $this->assertSame('Short note', $note->getName());
    }
}
