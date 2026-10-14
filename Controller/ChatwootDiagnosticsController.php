<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\MautomicCrmBundle\Integration\ChatwootDiagnostics;
use Symfony\Component\HttpFoundation\Response;

final class ChatwootDiagnosticsController extends CommonController
{
    public function indexAction(ChatwootDiagnostics $diagnostics): Response|array
    {
        if (!$this->security->isAdmin()) {
            return $this->accessDenied();
        }

        return $this->delegateView([
            'viewParameters' => $diagnostics->inspect(),
            'contentTemplate' => '@MautomicCrm/Settings/chatwoot.html.twig',
            'passthroughVars' => [
                'activeLink' => '#mautomic_crm_settings',
                'mauticContent' => 'mautomicCrmChatwootDiagnostics',
                'route' => $this->generateUrl('mautic_mautomic_crm_chatwoot_diagnostics'),
            ],
        ]);
    }
}
