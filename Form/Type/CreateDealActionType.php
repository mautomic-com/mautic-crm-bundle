<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Form\Type;

use MauticPlugin\MautomicCrmBundle\Entity\PipelineRepository;
use MauticPlugin\MautomicCrmBundle\Entity\StageRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CreateDealActionType extends AbstractType
{
    public function __construct(
        private PipelineRepository $pipelineRepository,
        private StageRepository $stageRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label'       => 'mautomic_crm.deal.name',
            'label_attr'  => ['class' => 'control-label'],
            'attr'        => [
                'class'       => 'form-control',
                'placeholder' => 'mautomic_crm.campaign.name_placeholder',
            ],
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

        $builder->add('pipeline', ChoiceType::class, [
            'label'       => 'mautomic_crm.deal.pipeline',
            'label_attr'  => ['class' => 'control-label'],
            'attr'        => ['class' => 'form-control'],
            'required'    => true,
            'constraints' => [new NotBlank(['message' => 'mautomic_crm.campaign.pipeline.required'])],
            'choices'     => $this->getPipelineChoices(),
        ]);

        $builder->add('stage', ChoiceType::class, [
            'label'       => 'mautomic_crm.deal.stage',
            'label_attr'  => ['class' => 'control-label'],
            'attr'        => ['class' => 'form-control'],
            'required'    => false,
            'placeholder' => 'mautomic_crm.campaign.first_stage',
            'choices'     => $this->getStageChoices(),
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'create_deal_action';
    }

    /**
     * @return array<string, int>
     */
    private function getPipelineChoices(): array
    {
        $pipelines = $this->pipelineRepository->createQueryBuilder('p')
            ->where('p.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

        $choices = [];
        foreach ($pipelines as $pipeline) {
            $choices[$pipeline->getName()] = $pipeline->getId();
        }

        return $choices;
    }

    /**
     * @return array<string, int>
     */
    private function getStageChoices(): array
    {
        $stages = $this->stageRepository->createQueryBuilder('s')
            ->join('s.pipeline', 'p')
            ->orderBy('p.name', 'ASC')
            ->addOrderBy('s.order', 'ASC')
            ->getQuery()
            ->getResult();

        $choices = [];
        foreach ($stages as $stage) {
            $label           = $stage->getPipeline()->getName().' > '.$stage->getName();
            $choices[$label] = $stage->getId();
        }

        return $choices;
    }
}
