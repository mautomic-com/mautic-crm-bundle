<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Integration;

final class ContactSyncDryRunAnalyzer
{
    public function __construct(
        private readonly InternationalPhoneNormalizer $normalizer,
        private readonly NormalizedPhoneIndex $phoneIndex,
    ) {
    }

    /**
     * @param list<array{id: mixed, phone?: mixed, mobile?: mixed}> $rows
     *
     * @return array<string, int>
     */
    public function analyze(array $rows): array
    {
        $index            = $this->phoneIndex->build($rows);
        $eligibleContacts = [];
        $invalidContacts  = [];
        $multiplePhones   = [];

        foreach ($rows as $row) {
            $contactId = filter_var($row['id'] ?? null, FILTER_VALIDATE_INT);
            if (false === $contactId || 0 >= $contactId) {
                continue;
            }

            $phones = array_values(array_unique(array_filter([
                $this->normalizer->normalize($row['phone'] ?? null),
                $this->normalizer->normalize($row['mobile'] ?? null),
            ])));

            if ([] === $phones) {
                $invalidContacts[$contactId] = true;
            } else {
                $eligibleContacts[$contactId] = true;
                if (1 < count($phones)) {
                    $multiplePhones[$contactId] = true;
                }
            }
        }

        $duplicateGroups   = array_filter($index, static fn (array $ids): bool => 1 < count($ids));
        $duplicateContacts = [];
        foreach ($duplicateGroups as $ids) {
            foreach ($ids as $id) {
                $duplicateContacts[$id] = true;
            }
        }

        return [
            'contacts_with_phone' => count($rows),
            'eligible_contacts'   => count($eligibleContacts),
            'invalid_contacts'    => count($invalidContacts),
            'duplicate_groups'    => count($duplicateGroups),
            'duplicate_contacts'  => count($duplicateContacts),
            'multiple_phones'     => count($multiplePhones),
            'safe_candidates'     => count(array_diff_key($eligibleContacts, $duplicateContacts, $multiplePhones)),
        ];
    }
}
