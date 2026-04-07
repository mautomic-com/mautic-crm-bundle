<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Form\Type;

use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\PipelineRepository;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use MauticPlugin\MautomicCrmBundle\Entity\StageRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CreateDealActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label'       => 'mautomic_crm.deal.name',
            'label_attr'  => ['class' => 'control-label'],
            'attr'        => ['class' => 'form-control'],
            'required'    => true,
            'constraints' => [new NotBlank(['message' => 'mautomic_crm.deal.name.required'])],
        ]);

        $builder->add('description', TextareaType::class, [
            'label'      => 'mautic.core.description',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => false,
        ]);

        $builder->add('amount', NumberType::class, [
            'label'      => 'mautomic_crm.deal.amount',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => false,
            'scale'      => 2,
        ]);

        $builder->add('currency', TextType::class, [
            'label'      => 'mautomic_crm.deal.currency',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control', 'maxlength' => 3],
            'required'   => false,
        ]);

        $builder->add('pipeline', EntityType::class, [
            'class'         => Pipeline::class,
            'choice_label'  => 'name',
            'label'         => 'mautomic_crm.deal.pipeline',
            'label_attr'    => ['class' => 'control-label'],
            'attr'          => ['class' => 'form-control'],
            'required'      => true,
            'constraints'   => [new NotBlank(['message' => 'mautomic_crm.campaign.pipeline.required'])],
            'query_builder' => fn (PipelineRepository $repo) => $repo->createQueryBuilder('p')
                ->where('p.isPublished = :published')
                ->setParameter('published', true)
                ->orderBy('p.name', 'ASC'),
        ]);

        $builder->add('stage', EntityType::class, [
            'class'         => Stage::class,
            'choice_label'  => 'name',
            'label'         => 'mautomic_crm.deal.stage',
            'label_attr'    => ['class' => 'control-label'],
            'attr'          => ['class' => 'form-control'],
            'required'      => false,
            'placeholder'   => 'mautomic_crm.campaign.first_stage',
            'query_builder' => fn (StageRepository $repo) => $repo->createQueryBuilder('s')
                ->orderBy('s.order', 'ASC'),
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'create_deal_action';
    }
}
