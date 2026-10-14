<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Integration;

final class ChatwootSettings
{
    public function __construct(
        private readonly string $configPath = '/etc/mautic/chatwoot.env',
    ) {
    }

    public function assignmentEnabled(): bool
    {
        return filter_var($this->all()['CHATWOOT_ASSIGNMENT_ENABLED'] ?? false, FILTER_VALIDATE_BOOL);
    }

    public function outboundEnabled(): bool
    {
        return filter_var($this->all()['CHATWOOT_OUTBOUND_ENABLED'] ?? false, FILTER_VALIDATE_BOOL);
    }

    public function baseUrl(): ?string
    {
        return $this->nonEmpty('CHATWOOT_BASE_URL');
    }

    public function apiToken(): ?string
    {
        return $this->nonEmpty('CHATWOOT_API_TOKEN');
    }

    public function accountId(): ?int
    {
        $value = $this->nonEmpty('CHATWOOT_ACCOUNT_ID');

        return null !== $value && ctype_digit($value) ? (int) $value : null;
    }

    public function phoneDefaultRegion(): ?string
    {
        $region = strtoupper((string) $this->nonEmpty('CHATWOOT_PHONE_DEFAULT_REGION'));

        return 1 === preg_match('/^[A-Z]{2}$/', $region) ? $region : null;
    }

    public function webhookSecretConfigured(): bool
    {
        return null !== $this->nonEmpty('CHATWOOT_WEBHOOK_SECRET');
    }

    public function isWebhookKeyValid(string $providedKey): bool
    {
        $secret = $this->nonEmpty('CHATWOOT_WEBHOOK_SECRET');

        return null !== $secret && hash_equals($secret, $providedKey);
    }

    /** @return array<int, int> */
    public function agentMap(): array
    {
        $settings = $this->all();
        $map      = [];

        if (!is_array($settings)) {
            return $map;
        }

        foreach (explode(',', (string) ($settings['CHATWOOT_AGENT_MAP'] ?? '')) as $pair) {
            $ids = array_map('trim', explode(':', $pair, 2));
            if (2 === count($ids) && ctype_digit($ids[0]) && ctype_digit($ids[1])) {
                $map[(int) $ids[0]] = (int) $ids[1];
            }
        }

        return $map;
    }

    /** @return array<string, mixed> */
    private function all(): array
    {
        $settings = @parse_ini_file($this->configPath, false, INI_SCANNER_RAW);

        return is_array($settings) ? $settings : [];
    }

    private function nonEmpty(string $key): ?string
    {
        $value = trim((string) ($this->all()[$key] ?? ''));

        return '' === $value ? null : $value;
    }
}
