<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use MauticPlugin\MautomicCrmBundle\Entity\Note;
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
