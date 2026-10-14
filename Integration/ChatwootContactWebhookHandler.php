<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Integration;

use Doctrine\DBAL\Connection;

final class ChatwootContactWebhookHandler
{
    public function __construct(
        private readonly Connection $connection,
        private readonly InternationalPhoneNormalizer $normalizer,
        private readonly NormalizedPhoneIndex $phoneIndex,
        private readonly ChatwootSettings $settings,
    ) {
    }

    /** @return array<string, int|string|null> */
    public function evaluate(array $payload): array
    {
        $event = $this->string($payload['event'] ?? null);
        if (!in_array($event, ['contact_created', 'contact_updated'], true)) {
            return ['contact_sync_status' => 'not_applicable'];
        }

        $contact = is_array($payload['contact'] ?? null) ? $payload['contact'] : $payload;
        $phone   = $this->normalizer->normalize($contact['phone_number'] ?? null);
        $account = is_array($payload['account'] ?? null) ? $payload['account'] : [];

        if ($this->settings->accountId() !== $this->positiveInteger($account['id'] ?? null)) {
            return [
                'contact_sync_status' => 'account_mismatch',
                'chatwoot_contact_id' => $this->positiveInteger($contact['id'] ?? null),
            ];
        }

        if (null === $phone) {
            return [
                'contact_sync_status' => 'invalid_phone',
                'chatwoot_contact_id' => $this->positiveInteger($contact['id'] ?? null),
            ];
        }

        $matches = $this->phoneIndex->build($this->contactPhoneRows())[$phone] ?? [];
        $status  = match (count($matches)) {
            0       => 'contact_not_found',
            1       => 'matched_read_only',
            default => 'contact_ambiguous',
        };

        return [
            'contact_sync_status' => $status,
            'chatwoot_contact_id' => $this->positiveInteger($contact['id'] ?? null),
            'mautic_contact_id'   => 1 === count($matches) ? $matches[0] : null,
            'match_count'         => count($matches),
            'phone_suffix'        => substr($phone, -4),
        ];
    }

    /** @return iterable<array{id: mixed, phone: mixed, mobile: mixed}> */
    private function contactPhoneRows(): iterable
    {
        $query = $this->connection->createQueryBuilder()
            ->select('id', 'phone', 'mobile')
            ->from(MAUTIC_TABLE_PREFIX.'leads')
            ->where("phone IS NOT NULL AND phone <> ''")
            ->orWhere("mobile IS NOT NULL AND mobile <> ''")
            ->orderBy('id', 'ASC');

        return $query->executeQuery()->iterateAssociative();
    }

    private function positiveInteger(mixed $value): ?int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT);

        return false !== $integer && 0 < $integer ? $integer : null;
    }

    private function string(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }
}
