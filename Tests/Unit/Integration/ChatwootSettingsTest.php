<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Integration;

use MauticPlugin\MautomicCrmBundle\Integration\ChatwootSettings;
use PHPUnit\Framework\TestCase;

final class ChatwootSettingsTest extends TestCase
{
    private string $configPath;

    protected function tearDown(): void
    {
        if (isset($this->configPath) && is_file($this->configPath)) {
            unlink($this->configPath);
        }
    }

    public function testReadsSafeConfigurationAndAgentMap(): void
    {
        $settings = $this->settings(<<<'INI'
CHATWOOT_BASE_URL=https://inbox.example.test/
CHATWOOT_API_TOKEN=test-token
CHATWOOT_ACCOUNT_ID=12
CHATWOOT_PHONE_DEFAULT_REGION=in
CHATWOOT_WEBHOOK_SECRET=test-secret
CHATWOOT_ASSIGNMENT_ENABLED=false
CHATWOOT_OUTBOUND_ENABLED=false
CHATWOOT_AGENT_MAP=2:7, 3:9, invalid, 4:x
INI);

        self::assertSame('https://inbox.example.test/', $settings->baseUrl());
        self::assertSame('test-token', $settings->apiToken());
        self::assertSame(12, $settings->accountId());
        self::assertSame('IN', $settings->phoneDefaultRegion());
        self::assertTrue($settings->webhookSecretConfigured());
        self::assertFalse($settings->assignmentEnabled());
        self::assertFalse($settings->outboundEnabled());
        self::assertTrue($settings->isWebhookKeyValid('test-secret'));
        self::assertFalse($settings->isWebhookKeyValid('wrong-secret'));
        self::assertSame([2 => 7, 3 => 9], $settings->agentMap());
    }

    public function testMissingOrInvalidValuesFailClosed(): void
    {
        $settings = $this->settings("CHATWOOT_ACCOUNT_ID=not-a-number\nCHATWOOT_PHONE_DEFAULT_REGION=India\nCHATWOOT_ASSIGNMENT_ENABLED=no\n");

        self::assertNull($settings->baseUrl());
        self::assertNull($settings->apiToken());
        self::assertNull($settings->accountId());
        self::assertNull($settings->phoneDefaultRegion());
        self::assertFalse($settings->webhookSecretConfigured());
        self::assertFalse($settings->assignmentEnabled());
        self::assertFalse($settings->outboundEnabled());
        self::assertFalse($settings->isWebhookKeyValid(''));
        self::assertSame([], $settings->agentMap());
    }

    private function settings(string $contents): ChatwootSettings
    {
        $this->configPath = tempnam(sys_get_temp_dir(), 'chatwoot-settings-');
        file_put_contents($this->configPath, $contents);

        return new ChatwootSettings($this->configPath);
    }
}
