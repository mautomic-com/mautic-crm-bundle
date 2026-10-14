<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Integration;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ChatwootDiagnostics
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ChatwootSettings $settings,
    ) {
    }

    /** @return array<string, mixed> */
    public function inspect(): array
    {
        $baseUrl   = $this->settings->baseUrl();
        $token     = $this->settings->apiToken();
        $accountId = $this->settings->accountId();
        $agentMap  = $this->settings->agentMap();

        $result = [
            'base_url' => $baseUrl,
            'api_token_configured' => null !== $token,
            'account_id' => $accountId,
            'webhook_secret_configured' => $this->settings->webhookSecretConfigured(),
            'assignment_enabled' => $this->settings->assignmentEnabled(),
            'agent_map' => $agentMap,
            'connection_status' => 'not_configured',
            'connected_name' => null,
            'account_visible' => null,
        ];

        if (null === $baseUrl || null === $token || null === $accountId) {
            return $result;
        }

        try {
            $response = $this->httpClient->request('GET', rtrim($baseUrl, '/').'/api/v1/profile', [
                'headers' => ['api_access_token' => $token],
                'timeout' => 10,
            ]);

            if (200 !== $response->getStatusCode()) {
                $result['connection_status'] = 'unavailable';

                return $result;
            }

            $profile = $response->toArray(false);
            $result['connection_status'] = 'connected';
            $result['connected_name'] = $this->safeString($profile['name'] ?? null);
            $result['account_visible'] = $this->accountVisible($profile['accounts'] ?? [], $accountId);
        } catch (ExceptionInterface) {
            $result['connection_status'] = 'unavailable';
        }

        return $result;
    }

    private function accountVisible(mixed $accounts, int $accountId): bool
    {
        if (!is_array($accounts)) {
            return false;
        }

        foreach ($accounts as $account) {
            if (is_array($account) && $accountId === (int) ($account['id'] ?? 0)) {
                return true;
            }
        }

        return false;
    }

    private function safeString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }
}
