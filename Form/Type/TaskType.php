<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Form\Type\UserListType;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\DealRepository;
use MauticPlugin\MautomicCrmBundle\Entity\Task;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Task>
 */
class TaskType extends AbstractType
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new CleanFormSubscriber([]));
        $builder->addEventSubscriber(new FormExitSubscriber('mautomic_crm.task', $options));

        $builder->add('title', TextType::class, [
            'label'      => 'mautomic_crm.task.title',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
        ]);

        $builder->add('description', TextareaType::class, [
            'label'      => 'mautic.core.description',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => false,
        ]);

        $builder->add('deal', EntityType::class, [
            'class'         => Deal::class,
            'choice_label'  => 'name',
            'label'         => 'mautomic_crm.task.deal',
            'label_attr'    => ['class' => 'control-label'],
            'attr'          => ['class' => 'form-control'],
            'required'      => false,
            'placeholder'   => 'mautomic_crm.task.deal.choose',
            'query_builder' => fn (DealRepository $repo) => $repo->createQueryBuilder('d')
                ->where('d.isPublished = :published')
                ->setParameter('published', true)
                ->orderBy('d.name', 'ASC'),
        ]);

        $builder->add('contact', EntityType::class, [
            'class'         => Lead::class,
            'choice_label'  => fn (Lead $lead) => $lead->getName() ?: $lead->getEmail() ?: 'Contact #'.$lead->getId(),
            'label'         => 'mautomic_crm.task.contact',
            'label_attr'    => ['class' => 'control-label'],
            'attr'          => ['class' => 'form-control'],
            'required'      => false,
            'placeholder'   => 'mautomic_crm.task.contact.choose',
            'query_builder' => fn (LeadRepository $repo) => $repo->createQueryBuilder('l')
                ->orderBy('l.lastname', 'ASC')
                ->addOrderBy('l.firstname', 'ASC')
                ->setMaxResults(200),
        ]);

        $builder->add('dueDate', DateTimeType::class, [
            'label'      => 'mautomic_crm.task.due_date',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'widget'     => 'single_text',
            'required'   => false,
        ]);

        $builder->add('priority', ChoiceType::class, [
            'label'      => 'mautomic_crm.task.priority',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'choices'    => [
                'mautomic_crm.task.priority.low'    => 'low',
                'mautomic_crm.task.priority.normal' => 'normal',
                'mautomic_crm.task.priority.high'   => 'high',
            ],
        ]);

        $builder->add('status', ChoiceType::class, [
            'label'      => 'mautomic_crm.task.status',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'choices'    => [
                'mautomic_crm.task.status.open'      => 'open',
                'mautomic_crm.task.status.completed' => 'completed',
            ],
        ]);

        $builder->add('reminderDate', DateTimeType::class, [
            'label'      => 'mautomic_crm.task.reminder_date',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'widget'     => 'single_text',
            'required'   => false,
        ]);

        $builder->add(
            $builder->create(
                'owner',
                UserListType::class,
                [
                    'label'      => 'mautomic_crm.task.owner',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'required'   => false,
                    'multiple'   => false,
                ]
            )
            ->addModelTransformer(new IdToEntityModelTransformer($this->entityManager, User::class))
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }

        $builder->add('buttons', FormButtonsType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}
