<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Integration;

use libphonenumber\PhoneNumberUtil;
use MauticPlugin\MautomicCrmBundle\Integration\ChatwootContactClient;
use MauticPlugin\MautomicCrmBundle\Integration\ChatwootSettings;
use MauticPlugin\MautomicCrmBundle\Integration\InternationalPhoneNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ChatwootContactClientTest extends TestCase
{
    private string $configPath;

    protected function tearDown(): void
    {
        if (isset($this->configPath) && is_file($this->configPath)) {
            unlink($this->configPath);
        }
    }

    public function testDisabledOutboundSyncMakesNoHttpRequests(): void
    {
        $this->configPath = tempnam(sys_get_temp_dir(), 'chatwoot-contact-');
        file_put_contents($this->configPath, <<<'INI'
CHATWOOT_BASE_URL=https://inbox.example.test
CHATWOOT_API_TOKEN=test-token
CHATWOOT_ACCOUNT_ID=12
CHATWOOT_PHONE_DEFAULT_REGION=IN
CHATWOOT_OUTBOUND_ENABLED=false
INI);
        $requestCount = 0;
        $httpClient   = new MockHttpClient(function () use (&$requestCount): MockResponse {
            ++$requestCount;

            return new MockResponse('{}');
        });
        $settings = new ChatwootSettings($this->configPath);
        $client   = new ChatwootContactClient(
            $httpClient,
            $settings,
            new InternationalPhoneNormalizer(PhoneNumberUtil::getInstance(), $settings),
        );

        self::assertSame(
            ['contact_sync_status' => 'disabled'],
            $client->sync(['id' => 42, 'phone' => '+442079460018']),
        );
        self::assertSame(0, $requestCount);
    }
}
