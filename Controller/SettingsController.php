<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SettingsController extends CommonController
{
    public function indexAction(): RedirectResponse
    {
        return $this->redirectToRoute('mautic_mautomic_crm_deal_field_index');
    }
}
