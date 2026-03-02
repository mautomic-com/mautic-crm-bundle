<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\DealRepository;
use MauticPlugin\MautomicCrmBundle\Entity\Note;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Note>
 */
class NoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('text', TextareaType::class, [
            'label'      => 'mautomic_crm.note.text',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control', 'rows' => 6],
        ]);

        $builder->add('type', ChoiceType::class, [
            'label'      => 'mautomic_crm.note.type',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'choices'    => [
                'mautomic_crm.note.type.general' => 'general',
                'mautomic_crm.note.type.call'    => 'call',
                'mautomic_crm.note.type.meeting' => 'meeting',
                'mautomic_crm.note.type.email'   => 'email',
            ],
        ]);

        $builder->add('deal', EntityType::class, [
            'class'         => Deal::class,
            'choice_label'  => 'name',
            'label'         => 'mautomic_crm.note.deal',
            'label_attr'    => ['class' => 'control-label'],
            'attr'          => ['class' => 'form-control'],
            'required'      => false,
            'placeholder'   => 'mautomic_crm.note.deal.choose',
            'query_builder' => fn (DealRepository $repo) => $repo->createQueryBuilder('d')
                ->where('d.isPublished = :published')
                ->setParameter('published', true)
                ->orderBy('d.name', 'ASC'),
        ]);

        $builder->add('contact', EntityType::class, [
            'class'         => Lead::class,
            'choice_label'  => fn (Lead $lead) => $lead->getName() ?: $lead->getEmail() ?: 'Contact #'.$lead->getId(),
            'label'         => 'mautomic_crm.note.contact',
            'label_attr'    => ['class' => 'control-label'],
            'attr'          => ['class' => 'form-control'],
            'required'      => false,
            'placeholder'   => 'mautomic_crm.note.contact.choose',
            'query_builder' => fn (LeadRepository $repo) => $repo->createQueryBuilder('l')
                ->orderBy('l.lastname', 'ASC')
                ->addOrderBy('l.firstname', 'ASC')
                ->setMaxResults(200),
        ]);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }

        $builder->add('buttons', FormButtonsType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Note::class,
        ]);
    }
}
