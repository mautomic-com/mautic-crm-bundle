<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Form\Type\UserListType;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\DealField;
use MauticPlugin\MautomicCrmBundle\Entity\DealFieldRepository;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\PipelineRepository;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use MauticPlugin\MautomicCrmBundle\Entity\StageRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Deal>
 */
class DealType extends AbstractType
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DealFieldRepository $dealFieldRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new CleanFormSubscriber([]));
        $builder->addEventSubscriber(new FormExitSubscriber('mautomic_crm.deal', $options));

        $builder->add('name', TextType::class, [
            'label'      => 'mautomic_crm.deal.name',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
        ]);

        $builder->add('description', TextareaType::class, [
            'label'      => 'mautic.core.description',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => false,
        ]);

        $builder->add('amount', MoneyType::class, [
            'label'      => 'mautomic_crm.deal.amount',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => false,
            'currency'   => false,
        ]);

        $builder->add('currency', TextType::class, [
            'label'      => 'mautomic_crm.deal.currency',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control', 'maxlength' => 3],
            'required'   => false,
        ]);

        $builder->add('closeDate', DateType::class, [
            'label'      => 'mautomic_crm.deal.close_date',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'widget'     => 'single_text',
            'required'   => false,
        ]);

        $builder->add('pipeline', EntityType::class, [
            'class'        => Pipeline::class,
            'choice_label' => 'name',
            'label'        => 'mautomic_crm.deal.pipeline',
            'label_attr'   => ['class' => 'control-label'],
            'attr'         => [
                'class'              => 'form-control',
                'data-stage-target'  => '#deal_stage',
            ],
            'required'      => true,
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
            'required'      => true,
            'query_builder' => fn (StageRepository $repo) => $repo->createQueryBuilder('s')
                ->orderBy('s.order', 'ASC'),
        ]);

        $builder->add(
            $builder->create(
                'owner',
                UserListType::class,
                [
                    'label'      => 'mautomic_crm.deal.owner',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'required'   => false,
                    'multiple'   => false,
                ]
            )
            ->addModelTransformer(new IdToEntityModelTransformer($this->entityManager, User::class))
        );

        $builder->add('category', CategoryListType::class, [
            'bundle' => 'plugin:mautomicCrm',
        ]);

        $builder->add('isPublished', YesNoButtonGroupType::class);

        $this->addCustomFields($builder, $options);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }

        $builder->add('buttons', FormButtonsType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'          => Deal::class,
            'custom_field_values' => [],
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function addCustomFields(FormBuilderInterface $builder, array $options): void
    {
        $publishedFields   = $this->dealFieldRepository->getPublishedFields();
        $customFieldValues = $options['custom_field_values'] ?? [];

        foreach ($publishedFields as $field) {
            $fieldName    = 'cf_'.$field->getAlias();
            $fieldOptions = $this->getFieldFormOptions($field);

            if (isset($customFieldValues[$field->getAlias()])) {
                $value = $customFieldValues[$field->getAlias()];

                if ('boolean' === $field->getType()) {
                    $value = (bool) $value ? 1 : 0;
                }

                $fieldOptions['options']['data'] = $value;
            }

            $builder->add($fieldName, $fieldOptions['type'], $fieldOptions['options']);
        }
    }

    /**
     * @return array{type: class-string, options: array<string, mixed>}
     */
    private function getFieldFormOptions(DealField $field): array
    {
        $baseOptions = [
            'label'      => $field->getLabel(),
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => $field->getIsRequired(),
            'mapped'     => false,
        ];

        return match ($field->getType()) {
            'textarea' => [
                'type'    => TextareaType::class,
                'options' => $baseOptions,
            ],
            'number' => [
                'type'    => NumberType::class,
                'options' => $baseOptions,
            ],
            'select' => [
                'type'    => ChoiceType::class,
                'options' => array_merge($baseOptions, [
                    'choices'     => $this->parseSelectOptions($field->getProperties()),
                    'placeholder' => '',
                    'required'    => $field->getIsRequired(),
                ]),
            ],
            'date' => [
                'type'    => DateType::class,
                'options' => array_merge($baseOptions, [
                    'widget' => 'single_text',
                ]),
            ],
            'boolean' => [
                'type'    => YesNoButtonGroupType::class,
                'options' => [
                    'label'    => $field->getLabel(),
                    'required' => $field->getIsRequired(),
                    'mapped'   => false,
                ],
            ],
            default => [
                'type'    => TextType::class,
                'options' => $baseOptions,
            ],
        };
    }

    /**
     * @return array<string, string>
     */
    private function parseSelectOptions(?string $properties): array
    {
        if (empty($properties)) {
            return [];
        }

        $options = [];
        foreach (explode('|', $properties) as $option) {
            $option = trim($option);
            if ('' !== $option) {
                $options[$option] = $option;
            }
        }

        return $options;
    }
}
