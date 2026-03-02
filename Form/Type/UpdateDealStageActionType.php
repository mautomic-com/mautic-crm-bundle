<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Form\Type;

use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\PipelineRepository;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use MauticPlugin\MautomicCrmBundle\Entity\StageRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UpdateDealStageActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('pipeline', EntityType::class, [
            'class'         => Pipeline::class,
            'choice_label'  => 'name',
            'label'         => 'mautomic_crm.campaign.pipeline',
            'label_attr'    => ['class' => 'control-label'],
            'attr'          => ['class' => 'form-control'],
            'required'      => true,
            'query_builder' => fn (PipelineRepository $repo) => $repo->createQueryBuilder('p')
                ->where('p.isPublished = :published')
                ->setParameter('published', true)
                ->orderBy('p.name', 'ASC'),
        ]);

        $builder->add('stage', EntityType::class, [
            'class'         => Stage::class,
            'choice_label'  => 'name',
            'label'         => 'mautomic_crm.campaign.stage',
            'label_attr'    => ['class' => 'control-label'],
            'attr'          => ['class' => 'form-control'],
            'required'      => true,
            'query_builder' => fn (StageRepository $repo) => $repo->createQueryBuilder('s')
                ->orderBy('s.order', 'ASC'),
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'update_deal_stage_action';
    }
}
