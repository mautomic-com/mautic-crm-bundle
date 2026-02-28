<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\DealRepository;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Event\DealEvent;
use MauticPlugin\MautomicCrmBundle\Form\Type\DealType;
use MauticPlugin\MautomicCrmBundle\MautomicCrmEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<Deal>
 */
class DealModel extends FormModel
{
    public function getActionRouteBase(): string
    {
        return 'mautomic_crm_deal';
    }

    public function getPermissionBase(): string
    {
        return 'mautomic_crm:deals';
    }

    public function getRepository(): DealRepository
    {
        return $this->em->getRepository(Deal::class);
    }

    public function getEntity($id = null): ?Deal
    {
        if (null === $id) {
            $deal = new Deal();

            $deal->setOwner($this->userHelper->getUser());
            $deal->setCloseDate(new \DateTime('today'));

            $defaultPipeline = $this->em->getRepository(Pipeline::class)
                ->createQueryBuilder('p')
                ->where('p.isPublished = :published')
                ->setParameter('published', true)
                ->orderBy('p.name', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($defaultPipeline instanceof Pipeline) {
                $deal->setPipeline($defaultPipeline);

                $firstStage = $defaultPipeline->getStages()->first();
                if ($firstStage instanceof \MauticPlugin\MautomicCrmBundle\Entity\Stage) {
                    $deal->setStage($firstStage);
                }
            }

            return $deal;
        }

        return parent::getEntity($id);
    }

    /**
     * @param Deal $entity
     */
    public function saveEntity($entity, $unlock = true): void
    {
        if ($entity instanceof Deal && null === $entity->getStage()) {
            $pipeline = $entity->getPipeline();
            if (null !== $pipeline) {
                $firstStage = $pipeline->getStages()->first();
                if ($firstStage instanceof \MauticPlugin\MautomicCrmBundle\Entity\Stage) {
                    $entity->setStage($firstStage);
                }
            }
        }

        parent::saveEntity($entity, $unlock);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof Deal) {
            throw new MethodNotAllowedHttpException(['Deal']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(DealType::class, $entity, $options);
    }

    protected function dispatchEvent($action, &$entity, $isNew = false, ?Event $event = null): ?Event
    {
        if (!$entity instanceof Deal) {
            throw new MethodNotAllowedHttpException(['Deal']);
        }

        $name = match ($action) {
            'pre_save'    => MautomicCrmEvents::DEAL_PRE_SAVE,
            'post_save'   => MautomicCrmEvents::DEAL_POST_SAVE,
            'pre_delete'  => MautomicCrmEvents::DEAL_PRE_DELETE,
            'post_delete' => MautomicCrmEvents::DEAL_POST_DELETE,
            default       => null,
        };

        if (null === $name) {
            return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new DealEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        }

        return null;
    }
}
