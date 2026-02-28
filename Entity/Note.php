<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Note extends FormEntity
{
    private ?int $id = null;

    private ?string $text = null;

    private string $type = 'general';

    private ?Deal $deal = null;

    private ?Lead $contact = null;

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('mautomic_notes')
            ->setCustomRepositoryClass(NoteRepository::class)
            ->addIndex(['note_type'], 'mautomic_note_type');

        $builder->addId();

        $builder->addField('text', 'text');

        $builder->createField('type', 'string')
            ->columnName('note_type')
            ->build();

        $builder->createManyToOne('deal', Deal::class)
            ->addJoinColumn('deal_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createManyToOne('contact', Lead::class)
            ->addJoinColumn('contact_id', 'id', true, false, 'SET NULL')
            ->build();
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('text', new NotBlank([
            'message' => 'mautomic_crm.note.text.required',
        ]));
    }

    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('note')
            ->addListProperties(['id', 'type', 'text'])
            ->addProperties(['deal', 'contact'])
            ->build();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return mb_substr($this->text ?? '', 0, 50);
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->isChanged('text', $text);
        $this->text = $text;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->isChanged('type', $type);
        $this->type = $type;

        return $this;
    }

    public function getDeal(): ?Deal
    {
        return $this->deal;
    }

    public function setDeal(?Deal $deal): self
    {
        $this->isChanged('deal', $deal);
        $this->deal = $deal;

        return $this;
    }

    public function getContact(): ?Lead
    {
        return $this->contact;
    }

    public function setContact(?Lead $contact): self
    {
        $this->isChanged('contact', $contact);
        $this->contact = $contact;

        return $this;
    }
}
