<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Form\Type;

use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Stage>
 */
class StageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label'      => 'mautomic_crm.stage.name',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
        ]);

        $builder->add('order', IntegerType::class, [
            'label'      => 'mautomic_crm.stage.order',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
        ]);

        $builder->add('probability', IntegerType::class, [
            'label'      => 'mautomic_crm.stage.probability',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control', 'min' => 0, 'max' => 100],
        ]);

        $builder->add('type', ChoiceType::class, [
            'label'      => 'mautomic_crm.stage.type',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'choices'    => [
                'mautomic_crm.stage.type.open' => 'open',
                'mautomic_crm.stage.type.won'  => 'won',
                'mautomic_crm.stage.type.lost' => 'lost',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stage::class,
        ]);
    }
}
