<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\DealRepository;

final class ChatwootDryRunRouter
{
    public function __construct(
        private readonly LeadRepository $leadRepository,
        private readonly DealRepository $dealRepository,
        private readonly ChatwootSettings $settings,
    ) {
    }

    /** @return array<string, int|string|null> */
    public function route(array $payload): array
    {
        $conversationId = $this->integer($this->first($this->at($payload, ['conversation', 'id']), $payload['conversation_id'] ?? null));
        $phone          = $this->string($this->first(
            $this->at($payload, ['contact', 'phone_number']),
            $payload['phone_number'] ?? null,
            $this->at($payload, ['conversation', 'meta', 'sender', 'phone_number']),
            $this->at($payload, ['sender', 'phone_number']),
        ));

        if (null === $phone || null === $conversationId) {
            return [
                'routing_status'  => 'insufficient_data',
                'conversation_id' => $conversationId,
            ];
        }

        $contacts = $this->contactsForPhone($phone);
        if (1 !== count($contacts)) {
            return [
                'routing_status' => 0 === count($contacts) ? 'contact_not_found' : 'contact_ambiguous',
                'conversation_id' => $conversationId,
                'phone_suffix' => $this->phoneSuffix($phone),
            ];
        }

        $contact     = reset($contacts);
        $ownerId     = $this->openDealOwnerId($contact);
        $ownerSource = 'open_deal';

        if (null === $ownerId) {
            $ownerId     = $contact->getOwner()?->getId();
            $ownerSource = 'contact';
        }
        $agentId     = null === $ownerId ? null : ($this->settings->agentMap()[$ownerId] ?? null);

        return [
            'routing_status'     => null === $ownerId ? 'owner_missing' : (null === $agentId ? 'agent_mapping_missing' : 'dry_run_ready'),
            'conversation_id'    => $conversationId,
            'contact_id'         => $contact->getId(),
            'mautic_owner_id'    => $ownerId,
            'owner_source'       => null === $ownerId ? null : $ownerSource,
            'chatwoot_agent_id'  => $agentId,
            'phone_suffix'       => $this->phoneSuffix($phone),
        ];
    }

    /** @return array<int, Lead> */
    private function contactsForPhone(string $phone): array
    {
        $contacts = [];

        foreach (['phone', 'mobile'] as $field) {
            foreach ($this->phoneCandidates($phone) as $candidate) {
                foreach ($this->leadRepository->getLeadsByFieldValue($field, $candidate) as $contact) {
                    if ($contact instanceof Lead) {
                        $contacts[$contact->getId()] = $contact;
                    }
                }
            }
        }

        return $contacts;
    }

    private function openDealOwnerId(Lead $contact): ?int
    {
        $deal = $this->dealRepository->createQueryBuilder('d')
            ->innerJoin('d.stage', 'stage')
            ->innerJoin('d.owner', 'owner')
            ->where('d.contact = :contact')
            ->andWhere('d.isPublished = :published')
            ->andWhere('stage.type = :type')
            ->setParameter('contact', $contact)
            ->setParameter('published', true)
            ->setParameter('type', 'open')
            ->orderBy('d.dateModified', 'DESC')
            ->addOrderBy('d.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        return $deal instanceof Deal ? $deal->getOwner()?->getId() : null;
    }

    /** @return string[] */
    private function phoneCandidates(string $phone): array
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        return array_values(array_unique(array_filter([$phone, $digits, '' === $digits ? null : '+'.$digits])));
    }

    private function phoneSuffix(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        return substr($digits, -4);
    }

    private function at(array $payload, array $path): mixed
    {
        $value = $payload;
        foreach ($path as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }
        return $value;
    }

    private function first(mixed ...$values): mixed
    {
        foreach ($values as $value) {
            if (null !== $value && '' !== $value) {
                return $value;
            }
        }
        return null;
    }

    private function string(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }

    private function integer(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
