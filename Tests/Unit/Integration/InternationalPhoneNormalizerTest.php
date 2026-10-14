<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Integration;

use libphonenumber\PhoneNumberUtil;
use MauticPlugin\MautomicCrmBundle\Integration\ChatwootSettings;
use MauticPlugin\MautomicCrmBundle\Integration\InternationalPhoneNormalizer;
use PHPUnit\Framework\TestCase;

final class InternationalPhoneNormalizerTest extends TestCase
{
    private InternationalPhoneNormalizer $normalizer;
    private string $configPath;

    protected function setUp(): void
    {
        $this->configPath = tempnam(sys_get_temp_dir(), 'chatwoot-phone-');
        file_put_contents($this->configPath, "CHATWOOT_PHONE_DEFAULT_REGION=IN\n");
        $this->normalizer = new InternationalPhoneNormalizer(
            PhoneNumberUtil::getInstance(),
            new ChatwootSettings($this->configPath),
        );
    }

    protected function tearDown(): void
    {
        if (is_file($this->configPath)) {
            unlink($this->configPath);
        }
    }

    public function testNormalizesValidInternationalNumbersToE164(): void
    {
        self::assertSame('+442079460018', $this->normalizer->normalize('+44 20 7946 0018'));
        self::assertSame('0018', $this->normalizer->suffix('+44 (0)20 7946 0018'));
    }

    public function testNormalizesValidNationalNumberWithConfiguredRegion(): void
    {
        self::assertSame('+919876543210', $this->normalizer->normalize('98765 43210'));
    }

    public function testNationalNumberFailsClosedWithoutConfiguredRegion(): void
    {
        file_put_contents($this->configPath, '');

        self::assertNull($this->normalizer->normalize('98765 43210'));
        self::assertSame('+442079460018', $this->normalizer->normalize('+44 20 7946 0018'));
    }

    public function testRejectsInvalidAndNonScalarValues(): void
    {
        self::assertNull($this->normalizer->normalize('123'));
        self::assertNull($this->normalizer->normalize('+123'));
        self::assertNull($this->normalizer->normalize(['+442079460018']));
    }
}
