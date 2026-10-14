<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Integration;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ChatwootContactClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ChatwootSettings $settings,
        private readonly InternationalPhoneNormalizer $normalizer,
    ) {
    }

    /**
     * This is the only contact mutation entry point. It performs no request unless
     * CHATWOOT_OUTBOUND_ENABLED is explicitly true and configuration is complete.
     *
     * @param array{id: int, name?: mixed, email?: mixed, phone?: mixed} $contact
     *
     * @return array<string, int|string|null>
     */
    public function sync(array $contact): array
    {
        if (!$this->settings->outboundEnabled()) {
            return ['contact_sync_status' => 'disabled'];
        }

        $baseUrl   = $this->settings->baseUrl();
        $token     = $this->settings->apiToken();
        $accountId = $this->settings->accountId();
        $phone     = $this->normalizer->normalize($contact['phone'] ?? null);
        $contactId = $contact['id'] ?? 0;

        if (null === $baseUrl || null === $token || null === $accountId || 0 >= $contactId || null === $phone) {
            return ['contact_sync_status' => 'configuration_error'];
        }

        $identifier = 'mautic:'.$contactId;

        try {
            $matches = $this->findExactMatches($baseUrl, $accountId, $token, $phone, $identifier);
            if (null === $matches) {
                return ['contact_sync_status' => 'api_error'];
            }

            if (1 < count($matches)) {
                return ['contact_sync_status' => 'duplicate_conflict', 'match_count' => count($matches)];
            }

            $payload = array_filter([
                'identifier'   => $identifier,
                'name'         => $this->string($contact['name'] ?? null),
                'email'        => $this->string($contact['email'] ?? null),
                'phone_number' => $phone,
            ], static fn (mixed $value): bool => null !== $value);

            if ([] === $matches) {
                $response = $this->httpClient->request('POST', sprintf(
                    '%s/api/v1/accounts/%d/contacts',
                    rtrim($baseUrl, '/'),
                    $accountId,
                ), $this->requestOptions($token, $payload));
                $status = 'created';
            } else {
                $chatwootId = (int) array_key_first($matches);
                $response   = $this->httpClient->request('PUT', sprintf(
                    '%s/api/v1/accounts/%d/contacts/%d',
                    rtrim($baseUrl, '/'),
                    $accountId,
                    $chatwootId,
                ), $this->requestOptions($token, $payload));
                $status = 'updated';
            }

            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface|\JsonException) {
            return ['contact_sync_status' => 'transport_error'];
        }

        return [
            'contact_sync_status' => 200 <= $statusCode && 300 > $statusCode ? $status : 'api_error',
            'http_status'         => $statusCode,
        ];
    }

    /** @return array<int, true>|null */
    private function findExactMatches(string $baseUrl, int $accountId, string $token, string $phone, string $identifier): ?array
    {
        $matches = [];

        foreach ([$phone, $identifier] as $query) {
            $response = $this->httpClient->request('GET', sprintf(
                '%s/api/v1/accounts/%d/contacts/search',
                rtrim($baseUrl, '/'),
                $accountId,
            ), [
                'headers' => ['api_access_token' => $token],
                'query'   => ['q' => $query],
                'timeout' => 10,
            ]);

            if (200 > $response->getStatusCode() || 300 <= $response->getStatusCode()) {
                return null;
            }

            $data = json_decode($response->getContent(false), true, 512, JSON_THROW_ON_ERROR);
            foreach ($this->contacts($data) as $candidate) {
                $id = filter_var($candidate['id'] ?? null, FILTER_VALIDATE_INT);
                if (false === $id || 0 >= $id) {
                    continue;
                }

                $candidatePhone      = $this->normalizer->normalize($candidate['phone_number'] ?? null);
                $candidateIdentifier = $this->string($candidate['identifier'] ?? null);
                if ($phone === $candidatePhone || $identifier === $candidateIdentifier) {
                    $matches[$id] = true;
                }
            }
        }

        return $matches;
    }

    /** @return list<array<string, mixed>> */
    private function contacts(mixed $data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $contacts = $data['payload'] ?? $data;
        if (!is_array($contacts) || !array_is_list($contacts)) {
            return [];
        }

        $validContacts = [];
        foreach ($contacts as $contact) {
            if (is_array($contact)) {
                $validContacts[] = $contact;
            }
        }

        return $validContacts;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function requestOptions(string $token, array $payload): array
    {
        return [
            'headers' => ['api_access_token' => $token],
            'json'    => $payload,
            'timeout' => 10,
        ];
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
