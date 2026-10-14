<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Integration;

use MauticPlugin\MautomicCrmBundle\Integration\ChatwootConversationClient;
use MauticPlugin\MautomicCrmBundle\Integration\ChatwootSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ChatwootConversationClientTest extends TestCase
{
    private string $configPath;

    protected function tearDown(): void
    {
        if (isset($this->configPath) && is_file($this->configPath)) {
            unlink($this->configPath);
        }
    }

    public function testUnmappedExecutiveCannotLoadConversations(): void
    {
        $requestCount = 0;
        $httpClient = new MockHttpClient(function () use (&$requestCount): MockResponse {
            ++$requestCount;

            return new MockResponse('{}');
        });
        $client = new ChatwootConversationClient($httpClient, $this->settings());

        self::assertSame(['conversations' => [], 'status' => 'unmapped'], $client->forMauticUser(99));
        self::assertSame(0, $requestCount);
    }

    public function testConversationListIsFilteredToMappedAgent(): void
    {
        $response = new MockResponse(json_encode(['data' => ['payload' => [
            ['id' => 10, 'status' => 'open', 'meta' => ['assignee' => ['id' => 7], 'sender' => ['name' => 'Allowed']]],
            ['id' => 11, 'status' => 'open', 'meta' => ['assignee' => ['id' => 8], 'sender' => ['name' => 'Hidden']]],
        ]]], JSON_THROW_ON_ERROR));
        $client = new ChatwootConversationClient(new MockHttpClient($response), $this->settings());

        $result = $client->forMauticUser(2);

        self::assertSame('ok', $result['status']);
        self::assertCount(1, $result['conversations']);
        self::assertSame(10, $result['conversations'][0]['id']);
        self::assertSame('Allowed', $result['conversations'][0]['contact_name']);
    }

    public function testExecutiveCannotOpenConversationAssignedToAnotherAgent(): void
    {
        $response = new MockResponse(json_encode([
            'id' => 11,
            'status' => 'open',
            'meta' => ['assignee' => ['id' => 8], 'sender' => ['name' => 'Hidden']],
            'messages' => [],
        ], JSON_THROW_ON_ERROR));
        $client = new ChatwootConversationClient(new MockHttpClient($response), $this->settings());

        $result = $client->detailForMauticUser(11, 2);

        self::assertSame('forbidden', $result['status']);
        self::assertNull($result['conversation']);
        self::assertSame([], $result['messages']);
    }

    public function testAdministratorCanOpenConversationAndMessagesAreSafelyMapped(): void
    {
        $response = new MockResponse(json_encode([
            'id' => 12,
            'status' => 'pending',
            'meta' => ['assignee' => ['id' => 8], 'sender' => ['name' => 'Customer']],
            'messages' => [[
                'id' => 50,
                'content' => '<script>alert(1)</script>',
                'message_type' => 0,
                'created_at' => 123456,
                'sender' => ['name' => 'Customer'],
            ]],
        ], JSON_THROW_ON_ERROR));
        $client = new ChatwootConversationClient(new MockHttpClient($response), $this->settings());

        $result = $client->detailForMauticUser(12, 1, true);

        self::assertSame('ok', $result['status']);
        self::assertSame(12, $result['conversation']['id']);
        self::assertSame('incoming', $result['messages'][0]['message_type']);
        self::assertSame('<script>alert(1)</script>', $result['messages'][0]['content']);
    }

    private function settings(): ChatwootSettings
    {
        $this->configPath = tempnam(sys_get_temp_dir(), 'chatwoot-conversation-');
        file_put_contents($this->configPath, <<<'INI'
CHATWOOT_BASE_URL=https://inbox.example.test
CHATWOOT_API_TOKEN=test-token
CHATWOOT_ACCOUNT_ID=12
CHATWOOT_AGENT_MAP=2:7
INI);

        return new ChatwootSettings($this->configPath);
    }
}
