<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Integration;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ChatwootConversationClient
{
    public function __construct(private readonly HttpClientInterface $httpClient, private readonly ChatwootSettings $settings) {}

    public function forMauticUser(int $userId, bool $showAll = false): array
    {
        $agentId = $this->settings->agentMap()[$userId] ?? null;
        $baseUrl = $this->settings->baseUrl();
        $token = $this->settings->apiToken();
        $accountId = $this->settings->accountId();
        if (!$showAll && null === $agentId) return ['conversations' => [], 'status' => 'unmapped'];
        if (null === $baseUrl || null === $token || null === $accountId) return ['conversations' => [], 'status' => 'unavailable'];

        try {
            $response = $this->httpClient->request('GET', sprintf('%s/api/v1/accounts/%d/conversations', rtrim($baseUrl, '/'), $accountId), ['headers' => ['api_access_token' => $token], 'query' => ['assignee_type' => 'all', 'status' => 'all'], 'timeout' => 10]);
            if (200 !== $response->getStatusCode()) return ['conversations' => [], 'status' => 'unavailable'];
            $body = $response->toArray(false);
        } catch (ExceptionInterface) {
            return ['conversations' => [], 'status' => 'unavailable'];
        }

        $conversations = [];
        foreach (($body['data']['payload'] ?? []) as $item) {
            if (is_array($item) && ($showAll || $agentId === (int) ($item['meta']['assignee']['id'] ?? 0))) {
                $conversations[] = ['id' => (int) ($item['id'] ?? 0), 'contact_name' => (string) ($item['meta']['sender']['name'] ?? ''), 'status' => (string) ($item['status'] ?? ''), 'last_activity_at' => (int) ($item['last_activity_at'] ?? 0)];
            }
        }
        return ['conversations' => $conversations, 'status' => 'ok'];
    }

    public function detailForMauticUser(int $conversationId, int $userId, bool $showAll = false): array
    {
        $agentId = $this->settings->agentMap()[$userId] ?? null;
        if (!$showAll && null === $agentId) {
            return ['conversation' => null, 'messages' => [], 'status' => 'forbidden'];
        }

        $baseUrl = $this->settings->baseUrl();
        $token = $this->settings->apiToken();
        $accountId = $this->settings->accountId();
        if (null === $baseUrl || null === $token || null === $accountId) {
            return ['conversation' => null, 'messages' => [], 'status' => 'unavailable'];
        }

        try {
            $response = $this->httpClient->request('GET', sprintf('%s/api/v1/accounts/%d/conversations/%d', rtrim($baseUrl, '/'), $accountId, $conversationId), [
                'headers' => ['api_access_token' => $token],
                'timeout' => 10,
            ]);
            $statusCode = $response->getStatusCode();
            if (404 === $statusCode) {
                return ['conversation' => null, 'messages' => [], 'status' => 'not_found'];
            }
            if (200 !== $statusCode) {
                return ['conversation' => null, 'messages' => [], 'status' => 'unavailable'];
            }
            $body = $response->toArray(false);
        } catch (ExceptionInterface) {
            return ['conversation' => null, 'messages' => [], 'status' => 'unavailable'];
        }

        $assigneeId = (int) ($body['meta']['assignee']['id'] ?? $body['assignee']['id'] ?? 0);
        if (!$showAll && $agentId !== $assigneeId) {
            return ['conversation' => null, 'messages' => [], 'status' => 'forbidden'];
        }

        $messages = [];
        foreach (($body['messages'] ?? []) as $message) {
            if (!is_array($message)) {
                continue;
            }
            $messages[] = [
                'id' => (int) ($message['id'] ?? 0),
                'content' => (string) ($message['content'] ?? ''),
                'message_type' => $this->messageType($message['message_type'] ?? null),
                'created_at' => (int) ($message['created_at'] ?? 0),
                'sender_name' => (string) ($message['sender']['name'] ?? ''),
            ];
        }

        return [
            'conversation' => [
                'id' => (int) ($body['id'] ?? $conversationId),
                'status' => (string) ($body['status'] ?? ''),
                'contact_name' => (string) ($body['meta']['sender']['name'] ?? ''),
            ],
            'messages' => $messages,
            'status' => 'ok',
        ];
    }

    private function messageType(mixed $value): string
    {
        return match ((int) $value) {
            0 => 'incoming',
            1 => 'outgoing',
            2 => 'activity',
            3 => 'template',
            default => 'unknown',
        };
    }
}
