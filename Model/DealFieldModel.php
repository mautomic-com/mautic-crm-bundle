<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\MautomicCrmBundle\Entity\DealField;
use MauticPlugin\MautomicCrmBundle\Entity\DealFieldRepository;
use MauticPlugin\MautomicCrmBundle\Form\Type\DealFieldType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<DealField>
 */
class DealFieldModel extends FormModel
{
    public function getActionRouteBase(): string
    {
        return 'mautomic_crm_deal_field';
    }

    public function getPermissionBase(): string
    {
        return 'mautomic_crm:deals';
    }

    public function getRepository(): DealFieldRepository
    {
        return $this->em->getRepository(DealField::class);
    }

    public function getEntity($id = null): ?DealField
    {
        if (null === $id) {
            return new DealField();
        }

        return parent::getEntity($id);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof DealField) {
            throw new MethodNotAllowedHttpException(['DealField']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(DealFieldType::class, $entity, $options);
    }

    public function generateAlias(string $label, ?int $excludeId = null): string
    {
        $alias = strtolower((string) preg_replace('/[^a-zA-Z0-9_]/', '_', $label));
        $alias = (string) preg_replace('/_+/', '_', $alias);
        $alias = trim($alias, '_');

        $baseAlias = $alias;
        $counter   = 1;

        while (true) {
            $existing = $this->getRepository()->getFieldByAlias($alias);
            if (null === $existing || (null !== $excludeId && $existing->getId() === $excludeId)) {
                break;
            }
            $alias = $baseAlias.'_'.$counter;
            ++$counter;
        }

        return $alias;
    }

    /**
     * @param DealField $entity
     */
    public function saveEntity($entity, $unlock = true): void
    {
        if ($entity instanceof DealField && empty($entity->getAlias())) {
            $entity->setAlias($this->generateAlias($entity->getLabel() ?? '', $entity->getId()));
        }

        parent::saveEntity($entity, $unlock);
    }

    protected function dispatchEvent($action, &$entity, $isNew = false, ?Event $event = null): ?Event
    {
        return null;
    }
}
