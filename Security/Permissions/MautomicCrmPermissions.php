<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class MautomicCrmPermissions extends AbstractPermissions
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params)
    {
        parent::__construct($params);
        $this->addStandardPermissions(['pipelines']);
        $this->addExtendedPermissions(['deals']);
        $this->addExtendedPermissions(['tasks']);
        $this->addExtendedPermissions(['notes']);
    }

    public function getName(): string
    {
        return 'mautomic_crm';
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $data
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('mautomic_crm', 'pipelines', $builder, $data);
        $this->addExtendedFormFields('mautomic_crm', 'deals', $builder, $data);
        $this->addExtendedFormFields('mautomic_crm', 'tasks', $builder, $data);
        $this->addExtendedFormFields('mautomic_crm', 'notes', $builder, $data);
    }
}
