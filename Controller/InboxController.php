<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\MautomicCrmBundle\Integration\ChatwootConversationClient;
use Symfony\Component\HttpFoundation\Response;

final class InboxController extends CommonController
{
    public function indexAction(ChatwootConversationClient $client): Response|array
    {
        if (!$this->security->isGranted('mautomic_crm:inbox:view')) {
            return $this->accessDenied();
        }

        $result = $client->forMauticUser((int) $this->user?->getId(), $this->security->isAdmin());

        return $this->delegateView([
            'viewParameters' => $result,
            'contentTemplate' => '@MautomicCrm/Inbox/index.html.twig',
            'passthroughVars' => ['activeLink' => '#mautomic_crm_inbox', 'mauticContent' => 'mautomicCrmInbox', 'route' => $this->generateUrl('mautic_mautomic_crm_inbox_index')],
        ]);
    }

    public function viewAction(int $conversationId, ChatwootConversationClient $client): Response|array
    {
        if (!$this->security->isGranted('mautomic_crm:inbox:view')) {
            return $this->accessDenied();
        }

        $result = $client->detailForMauticUser($conversationId, (int) $this->user?->getId(), $this->security->isAdmin());

        if (in_array($result['status'], ['forbidden', 'not_found'], true)) {
            throw $this->createNotFoundException();
        }

        return $this->delegateView([
            'viewParameters' => $result,
            'contentTemplate' => '@MautomicCrm/Inbox/details.html.twig',
            'passthroughVars' => [
                'activeLink' => '#mautomic_crm_inbox',
                'mauticContent' => 'mautomicCrmInbox',
                'route' => $this->generateUrl('mautic_mautomic_crm_inbox_view', ['conversationId' => $conversationId]),
            ],
        ]);
    }
}
