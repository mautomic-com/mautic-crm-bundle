<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Integration;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

final class InternationalPhoneNormalizer
{
    public function __construct(
        private readonly PhoneNumberUtil $phoneNumberUtil,
        private readonly ChatwootSettings $settings,
    ) {
    }

    public function normalize(mixed $phone): ?string
    {
        if (!is_scalar($phone)) {
            return null;
        }

        $phone = trim((string) $phone);
        if ('' === $phone) {
            return null;
        }

        $region = null;
        if (!str_starts_with($phone, '+')) {
            $region = $this->settings->phoneDefaultRegion();
            if (null === $region || !in_array($region, $this->phoneNumberUtil->getSupportedRegions(), true)) {
                return null;
            }
        }

        try {
            $number = $this->phoneNumberUtil->parse($phone, $region);
        } catch (NumberParseException) {
            return null;
        }

        if (!$this->phoneNumberUtil->isValidNumber($number)) {
            return null;
        }

        $normalized = $this->phoneNumberUtil->format($number, PhoneNumberFormat::E164);

        return preg_match('/^\+[1-9]\d{1,14}$/', $normalized) ? $normalized : null;
    }

    public function suffix(mixed $phone): ?string
    {
        $normalized = $this->normalize($phone);

        return null === $normalized ? null : substr($normalized, -4);
    }
}
