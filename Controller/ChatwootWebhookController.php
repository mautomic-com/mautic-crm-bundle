<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Controller;

use MauticPlugin\MautomicCrmBundle\Integration\ChatwootAssignmentClient;
use MauticPlugin\MautomicCrmBundle\Integration\ChatwootContactWebhookHandler;
use MauticPlugin\MautomicCrmBundle\Integration\ChatwootDryRunRouter;
use MauticPlugin\MautomicCrmBundle\Integration\ChatwootSettings;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ChatwootWebhookController
{
    private const SUPPORTED_EVENTS = [
        'conversation_created',
        'conversation_status_changed',
        'conversation_updated',
        'message_created',
        'message_updated',
        'contact_created',
        'contact_updated',
    ];

    public function __construct(
        #[Autowire(service: 'monolog.logger.mautic')]
        private readonly LoggerInterface $logger,
        private readonly ChatwootDryRunRouter $router,
        private readonly ChatwootAssignmentClient $assignmentClient,
        private readonly ChatwootContactWebhookHandler $contactWebhookHandler,
        private readonly ChatwootSettings $settings,
    ) {
    }

    public function webhookAction(Request $request): JsonResponse
    {
        if (!$this->settings->isWebhookKeyValid((string) $request->query->get('key', ''))) {
            $this->logger->warning('Mautomic Chatwoot webhook rejected: invalid webhook key.');

            return new JsonResponse(
                ['status' => 'error', 'message' => 'Invalid webhook key.'],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $this->logger->warning('Mautomic Chatwoot webhook rejected: malformed JSON.');

            return new JsonResponse(
                ['status' => 'error', 'message' => 'Malformed JSON payload.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        if (!is_array($payload) || array_is_list($payload)) {
            $this->logger->warning('Mautomic Chatwoot webhook rejected: JSON payload must be an object.');

            return new JsonResponse(
                ['status' => 'error', 'message' => 'JSON payload must be an object.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $event = $this->stringValue($payload['event'] ?? null);
        $messageContent = $this->firstString(
            $this->valueAt($payload, ['message', 'content']),
            $payload['content'] ?? null,
            $payload['message_content'] ?? null,
        );

        $this->logger->notice('Mautomic Chatwoot webhook received.', [
            'event'                  => $event,
            'event_supported'        => null !== $event && in_array($event, self::SUPPORTED_EVENTS, true),
            'account_id'             => $this->firstString($this->valueAt($payload, ['account', 'id']), $payload['account_id'] ?? null),
            'inbox_id'               => $this->firstString($this->valueAt($payload, ['inbox', 'id']), $payload['inbox_id'] ?? null, $this->valueAt($payload, ['conversation', 'inbox_id'])),
            'conversation_id'        => $this->firstString($this->valueAt($payload, ['conversation', 'id']), $payload['conversation_id'] ?? null),
            'contact_id'             => $this->firstString($this->valueAt($payload, ['contact', 'id']), $payload['contact_id'] ?? null, $this->valueAt($payload, ['conversation', 'meta', 'sender', 'id']), $this->valueAt($payload, ['sender', 'id'])),
            'phone_present'          => null !== $this->firstString($this->valueAt($payload, ['contact', 'phone_number']), $payload['phone_number'] ?? null, $this->valueAt($payload, ['conversation', 'meta', 'sender', 'phone_number']), $this->valueAt($payload, ['sender', 'phone_number'])),
            'message_id'             => $this->firstString($this->valueAt($payload, ['message', 'id']), $payload['message_id'] ?? null, str_starts_with((string) $event, 'message_') ? ($payload['id'] ?? null) : null),
            'message_type'           => $this->firstString($this->valueAt($payload, ['message', 'message_type']), $payload['message_type'] ?? null, $payload['direction'] ?? null),
            'sender_id'              => $this->firstString($this->valueAt($payload, ['sender', 'id']), $payload['sender_id'] ?? null, $this->valueAt($payload, ['message', 'sender', 'id'])),
            'conversation_status'    => $this->firstString($this->valueAt($payload, ['conversation', 'status']), $payload['conversation_status'] ?? null, $payload['status'] ?? null),
            'message_content_length' => null === $messageContent ? null : strlen($messageContent),
        ]);

        if (in_array($event, ['contact_created', 'contact_updated'], true)) {
            $this->logger->notice(
                'Mautomic Chatwoot contact sync evaluated.',
                $this->contactWebhookHandler->evaluate($payload),
            );
        } else {
            $routing = $this->router->route($payload);
            $this->logger->notice('Mautomic Chatwoot routing evaluated.', $routing);
            $this->logger->notice('Mautomic Chatwoot assignment evaluated.', $this->assignmentClient->assign($routing));
        }

        return new JsonResponse(['status' => 'ok']);
    }

    private function valueAt(array $payload, array $path): mixed
    {
        $value = $payload;

        foreach ($path as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }

            $value = $value[$key];
        }

        return $value;
    }

    private function firstString(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            $string = $this->stringValue($value);

            if (null !== $string) {
                return $string;
            }
        }

        return null;
    }

    private function stringValue(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }
}
