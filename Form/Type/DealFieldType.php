<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use MauticPlugin\MautomicCrmBundle\Entity\DealField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<DealField>
 */
class DealFieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new CleanFormSubscriber([]));
        $builder->addEventSubscriber(new FormExitSubscriber('mautomic_crm.deal_field', $options));

        $builder->add('label', TextType::class, [
            'label'      => 'mautomic_crm.deal_field.label',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
        ]);

        $builder->add('alias', TextType::class, [
            'label'      => 'mautomic_crm.deal_field.alias',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => false,
        ]);

        $builder->add('type', ChoiceType::class, [
            'label'      => 'mautomic_crm.deal_field.type',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'choices'    => [
                'mautomic_crm.deal_field.type.text'     => 'text',
                'mautomic_crm.deal_field.type.textarea' => 'textarea',
                'mautomic_crm.deal_field.type.number'   => 'number',
                'mautomic_crm.deal_field.type.select'   => 'select',
                'mautomic_crm.deal_field.type.date'     => 'date',
                'mautomic_crm.deal_field.type.boolean'  => 'boolean',
            ],
        ]);

        $builder->add('isRequired', YesNoButtonGroupType::class, [
            'label' => 'mautomic_crm.deal_field.is_required',
        ]);

        $builder->add('fieldGroup', TextType::class, [
            'label'      => 'mautomic_crm.deal_field.group',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => false,
        ]);

        $builder->add('fieldOrder', NumberType::class, [
            'label'      => 'mautomic_crm.deal_field.order',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => false,
        ]);

        $builder->add('properties', TextareaType::class, [
            'label'      => 'mautomic_crm.deal_field.properties',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'       => 'form-control',
                'placeholder' => 'mautomic_crm.deal_field.properties.help',
            ],
            'required' => false,
        ]);

        $builder->add('isPublished', YesNoButtonGroupType::class);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }

        $builder->add('buttons', FormButtonsType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DealField::class,
        ]);
    }
}
