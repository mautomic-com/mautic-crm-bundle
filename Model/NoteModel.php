<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\MautomicCrmBundle\Entity\Note;
use MauticPlugin\MautomicCrmBundle\Entity\NoteRepository;
use MauticPlugin\MautomicCrmBundle\Form\Type\NoteType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * @extends FormModel<Note>
 */
class NoteModel extends FormModel
{
    public function getActionRouteBase(): string
    {
        return 'mautomic_crm_note';
    }

    public function getPermissionBase(): string
    {
        return 'mautomic_crm:notes';
    }

    public function getRepository(): NoteRepository
    {
        return $this->em->getRepository(Note::class);
    }

    public function getEntity($id = null): ?Note
    {
        if (null === $id) {
            return new Note();
        }

        return parent::getEntity($id);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof Note) {
            throw new MethodNotAllowedHttpException(['Note']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(NoteType::class, $entity, $options);
    }

    protected function dispatchEvent($action, &$entity, $isNew = false, ?\Symfony\Contracts\EventDispatcher\Event $event = null): ?\Symfony\Contracts\EventDispatcher\Event
    {
        return null;
    }
}
