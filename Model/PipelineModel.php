<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\PipelineRepository;
use MauticPlugin\MautomicCrmBundle\Form\Type\PipelineType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * @extends FormModel<Pipeline>
 */
class PipelineModel extends FormModel
{
    public function getActionRouteBase(): string
    {
        return 'mautomic_crm_pipeline';
    }

    public function getPermissionBase(): string
    {
        return 'mautomic_crm:pipelines';
    }

    public function getRepository(): PipelineRepository
    {
        return $this->em->getRepository(Pipeline::class);
    }

    public function getEntity($id = null): ?Pipeline
    {
        if (null === $id) {
            return new Pipeline();
        }

        return parent::getEntity($id);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof Pipeline) {
            throw new MethodNotAllowedHttpException(['Pipeline']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(PipelineType::class, $entity, $options);
    }

    protected function dispatchEvent($action, &$entity, $isNew = false, ?\Symfony\Contracts\EventDispatcher\Event $event = null): ?\Symfony\Contracts\EventDispatcher\Event
    {
        return null;
    }
}
