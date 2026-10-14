<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Integration;

use MauticPlugin\MautomicCrmBundle\Integration\ChatwootDiagnostics;
use MauticPlugin\MautomicCrmBundle\Integration\ChatwootSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ChatwootDiagnosticsTest extends TestCase
{
    private string $configPath;

    protected function tearDown(): void
    {
        if (isset($this->configPath) && is_file($this->configPath)) {
            unlink($this->configPath);
        }
    }

    public function testMissingConfigurationDoesNotCallChatwoot(): void
    {
        $this->configPath = tempnam(sys_get_temp_dir(), 'chatwoot-diagnostics-');
        file_put_contents($this->configPath, '');
        $requestCount = 0;
        $httpClient = new MockHttpClient(function () use (&$requestCount): MockResponse {
            ++$requestCount;

            return new MockResponse('{}');
        });

        $result = (new ChatwootDiagnostics($httpClient, new ChatwootSettings($this->configPath)))->inspect();

        self::assertSame('not_configured', $result['connection_status']);
        self::assertFalse($result['api_token_configured']);
        self::assertSame(0, $requestCount);
    }

    public function testConnectedProfileReportsOnlySafeMetadata(): void
    {
        $this->configPath = tempnam(sys_get_temp_dir(), 'chatwoot-diagnostics-');
        file_put_contents($this->configPath, <<<'INI'
CHATWOOT_BASE_URL=https://inbox.example.test
CHATWOOT_API_TOKEN=super-secret-token
CHATWOOT_ACCOUNT_ID=12
CHATWOOT_WEBHOOK_SECRET=super-secret-webhook
CHATWOOT_ASSIGNMENT_ENABLED=false
CHATWOOT_AGENT_MAP=2:7
INI);
        $response = new MockResponse(json_encode([
            'name' => 'CRM Administrator',
            'accounts' => [['id' => 12, 'name' => 'CRM']],
        ], JSON_THROW_ON_ERROR));

        $result = (new ChatwootDiagnostics(new MockHttpClient($response), new ChatwootSettings($this->configPath)))->inspect();

        self::assertSame('connected', $result['connection_status']);
        self::assertSame('CRM Administrator', $result['connected_name']);
        self::assertTrue($result['account_visible']);
        self::assertTrue($result['api_token_configured']);
        self::assertTrue($result['webhook_secret_configured']);
        self::assertFalse($result['assignment_enabled']);
        self::assertSame([2 => 7], $result['agent_map']);
        self::assertArrayNotHasKey('api_token', $result);
        self::assertArrayNotHasKey('webhook_secret', $result);
    }

    public function testFailedAuthenticationIsReportedAsUnavailable(): void
    {
        $this->configPath = tempnam(sys_get_temp_dir(), 'chatwoot-diagnostics-');
        file_put_contents($this->configPath, <<<'INI'
CHATWOOT_BASE_URL=https://inbox.example.test
CHATWOOT_API_TOKEN=bad-token
CHATWOOT_ACCOUNT_ID=12
INI);
        $response = new MockResponse('{"error":"unauthorized"}', ['http_code' => 401]);

        $result = (new ChatwootDiagnostics(new MockHttpClient($response), new ChatwootSettings($this->configPath)))->inspect();

        self::assertSame('unavailable', $result['connection_status']);
        self::assertNull($result['connected_name']);
    }
}
