<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Integration;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ChatwootAssignmentClient
{
    public function __construct(private readonly HttpClientInterface $httpClient, private readonly ChatwootSettings $settings)
    {
    }

    /** @param array<string, int|string|null> $routing */
    public function assign(array $routing): array
    {
        if ('dry_run_ready' !== ($routing['routing_status'] ?? null)) {
            return ['assignment_status' => 'not_applicable'];
        }

        $conversationId = (int) ($routing['conversation_id'] ?? 0);
        $agentId        = (int) ($routing['chatwoot_agent_id'] ?? 0);

        if (!$this->settings->assignmentEnabled()) {
            return ['assignment_status' => 'disabled', 'conversation_id' => $conversationId, 'chatwoot_agent_id' => $agentId];
        }

        $baseUrl   = $this->settings->baseUrl();
        $token     = $this->settings->apiToken();
        $accountId = $this->settings->accountId();

        if (null === $baseUrl || null === $token || null === $accountId || 0 >= $conversationId || 0 >= $agentId) {
            return ['assignment_status' => 'configuration_error'];
        }

        try {
            $response = $this->httpClient->request('POST', sprintf('%s/api/v1/accounts/%d/conversations/%d/assignments', rtrim($baseUrl, '/'), $accountId, $conversationId), [
                'headers' => ['api_access_token' => $token],
                'json'    => ['assignee_id' => $agentId],
                'timeout' => 10,
            ]);
            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface) {
            return ['assignment_status' => 'transport_error', 'conversation_id' => $conversationId];
        }

        return [
            'assignment_status' => 200 <= $statusCode && 300 > $statusCode ? 'assigned' : 'api_error',
            'http_status'       => $statusCode,
            'conversation_id'   => $conversationId,
            'chatwoot_agent_id' => $agentId,
        ];
    }
}
