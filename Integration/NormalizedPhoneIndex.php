<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Integration;

final class NormalizedPhoneIndex
{
    public function __construct(private readonly InternationalPhoneNormalizer $normalizer)
    {
    }

    /**
     * @param iterable<array{id: mixed, phone?: mixed, mobile?: mixed}> $rows
     *
     * @return array<string, list<int>>
     */
    public function build(iterable $rows): array
    {
        $index = [];

        foreach ($rows as $row) {
            $contactId = filter_var($row['id'] ?? null, FILTER_VALIDATE_INT);
            if (false === $contactId || 0 >= $contactId) {
                continue;
            }

            foreach (array_unique([
                $this->normalizer->normalize($row['phone'] ?? null),
                $this->normalizer->normalize($row['mobile'] ?? null),
            ]) as $phone) {
                if (null !== $phone) {
                    $index[$phone][$contactId] = $contactId;
                }
            }
        }

        return array_map(static fn (array $ids): array => array_values($ids), $index);
    }
}
