<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Integration;

use MauticPlugin\MautomicCrmBundle\Integration\ChatwootAssignmentClient;
use MauticPlugin\MautomicCrmBundle\Integration\ChatwootSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ChatwootAssignmentClientTest extends TestCase
{
    private string $configPath;

    protected function tearDown(): void
    {
        if (isset($this->configPath) && is_file($this->configPath)) {
            unlink($this->configPath);
        }
    }

    public function testDisabledAssignmentNeverCallsChatwoot(): void
    {
        $this->configPath = tempnam(sys_get_temp_dir(), 'chatwoot-assignment-');
        file_put_contents($this->configPath, <<<'INI'
CHATWOOT_BASE_URL=https://inbox.example.test
CHATWOOT_API_TOKEN=test-token
CHATWOOT_ACCOUNT_ID=12
CHATWOOT_ASSIGNMENT_ENABLED=false
INI);
        $requestCount = 0;
        $httpClient = new MockHttpClient(function () use (&$requestCount): MockResponse {
            ++$requestCount;

            return new MockResponse('{}');
        });
        $client = new ChatwootAssignmentClient($httpClient, new ChatwootSettings($this->configPath));

        $result = $client->assign([
            'routing_status' => 'dry_run_ready',
            'conversation_id' => 100,
            'chatwoot_agent_id' => 7,
        ]);

        self::assertSame('disabled', $result['assignment_status']);
        self::assertSame(0, $requestCount);
    }

    public function testNonReadyRoutingIsNotApplicable(): void
    {
        $this->configPath = tempnam(sys_get_temp_dir(), 'chatwoot-assignment-');
        file_put_contents($this->configPath, '');
        $client = new ChatwootAssignmentClient(new MockHttpClient(), new ChatwootSettings($this->configPath));

        self::assertSame(
            ['assignment_status' => 'not_applicable'],
            $client->assign(['routing_status' => 'contact_not_found']),
        );
    }
}
