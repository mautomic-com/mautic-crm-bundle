<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Integration;

use Doctrine\DBAL\Connection;
use libphonenumber\PhoneNumberUtil;
use MauticPlugin\MautomicCrmBundle\Integration\ChatwootContactWebhookHandler;
use MauticPlugin\MautomicCrmBundle\Integration\ChatwootSettings;
use MauticPlugin\MautomicCrmBundle\Integration\InternationalPhoneNormalizer;
use MauticPlugin\MautomicCrmBundle\Integration\NormalizedPhoneIndex;
use PHPUnit\Framework\TestCase;

final class ChatwootContactWebhookHandlerTest extends TestCase
{
    private string $configPath;

    protected function tearDown(): void
    {
        if (isset($this->configPath) && is_file($this->configPath)) {
            unlink($this->configPath);
        }
    }

    public function testRejectsAContactEventFromAnotherAccountBeforeDatabaseLookup(): void
    {
        $this->configPath = tempnam(sys_get_temp_dir(), 'chatwoot-webhook-');
        file_put_contents($this->configPath, "CHATWOOT_ACCOUNT_ID=12\nCHATWOOT_PHONE_DEFAULT_REGION=IN\n");
        $settings   = new ChatwootSettings($this->configPath);
        $normalizer = new InternationalPhoneNormalizer(PhoneNumberUtil::getInstance(), $settings);
        $handler    = new ChatwootContactWebhookHandler(
            $this->createMock(Connection::class),
            $normalizer,
            new NormalizedPhoneIndex($normalizer),
            $settings,
        );

        self::assertSame([
            'contact_sync_status' => 'account_mismatch',
            'chatwoot_contact_id' => 99,
        ], $handler->evaluate([
            'event'        => 'contact_updated',
            'id'           => 99,
            'phone_number' => '+442079460018',
            'account'      => ['id' => 13],
        ]));
    }

    public function testIgnoresNonContactEvents(): void
    {
        $this->configPath = tempnam(sys_get_temp_dir(), 'chatwoot-webhook-');
        file_put_contents($this->configPath, "CHATWOOT_ACCOUNT_ID=12\nCHATWOOT_PHONE_DEFAULT_REGION=IN\n");
        $settings   = new ChatwootSettings($this->configPath);
        $normalizer = new InternationalPhoneNormalizer(PhoneNumberUtil::getInstance(), $settings);
        $handler    = new ChatwootContactWebhookHandler(
            $this->createMock(Connection::class),
            $normalizer,
            new NormalizedPhoneIndex($normalizer),
            $settings,
        );

        self::assertSame(
            ['contact_sync_status' => 'not_applicable'],
            $handler->evaluate(['event' => 'message_created']),
        );
    }
}
