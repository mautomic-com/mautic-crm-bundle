<?php

declare(strict_types=1);

return [
    'name'        => 'Mautomic CRM',
    'description' => 'HubSpot-style CRM for Mautic — deals, pipelines, tasks, and notes.',
    'version'     => '1.0.0',
    'author'      => 'Mautomic',

    'routes' => [
        'main' => [
            'mautic_mautomic_crm_pipeline_index' => [
                'path'       => '/mautomic/pipelines/{page}',
                'controller' => 'MauticPlugin\MautomicCrmBundle\Controller\PipelineController::indexAction',
            ],
            'mautic_mautomic_crm_pipeline_action' => [
                'path'       => '/mautomic/pipelines/{objectAction}/{objectId}',
                'controller' => 'MauticPlugin\MautomicCrmBundle\Controller\PipelineController::executeAction',
            ],
            'mautic_mautomic_crm_deal_index' => [
                'path'       => '/mautomic/deals/{page}',
                'controller' => 'MauticPlugin\MautomicCrmBundle\Controller\DealController::indexAction',
            ],
            'mautic_mautomic_crm_deal_action' => [
                'path'       => '/mautomic/deals/{objectAction}/{objectId}',
                'controller' => 'MauticPlugin\MautomicCrmBundle\Controller\DealController::executeAction',
            ],
            'mautic_mautomic_crm_task_index' => [
                'path'       => '/mautomic/tasks/{page}',
                'controller' => 'MauticPlugin\MautomicCrmBundle\Controller\TaskController::indexAction',
            ],
            'mautic_mautomic_crm_task_action' => [
                'path'       => '/mautomic/tasks/{objectAction}/{objectId}',
                'controller' => 'MauticPlugin\MautomicCrmBundle\Controller\TaskController::executeAction',
            ],
            'mautic_mautomic_crm_note_action' => [
                'path'       => '/mautomic/notes/{objectAction}/{objectId}',
                'controller' => 'MauticPlugin\MautomicCrmBundle\Controller\NoteController::executeAction',
            ],
        ],
        'api' => [
            'mautomic_api_pipelines' => [
                'standard_entity' => true,
                'name'            => 'mautomic_pipelines',
                'path'            => '/mautomic/pipelines',
                'controller'      => MauticPlugin\MautomicCrmBundle\Controller\Api\PipelineApiController::class,
            ],
            'mautomic_api_deals' => [
                'standard_entity' => true,
                'name'            => 'mautomic_deals',
                'path'            => '/mautomic/deals',
                'controller'      => MauticPlugin\MautomicCrmBundle\Controller\Api\DealApiController::class,
            ],
            'mautomic_api_tasks' => [
                'standard_entity' => true,
                'name'            => 'mautomic_tasks',
                'path'            => '/mautomic/tasks',
                'controller'      => MauticPlugin\MautomicCrmBundle\Controller\Api\TaskApiController::class,
            ],
            'mautomic_api_notes' => [
                'standard_entity' => true,
                'name'            => 'mautomic_notes',
                'path'            => '/mautomic/notes',
                'controller'      => MauticPlugin\MautomicCrmBundle\Controller\Api\NoteApiController::class,
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'mautomic_crm.crm' => [
                'id'        => 'mautomic_crm_root',
                'iconClass' => 'ri-briefcase-line',
                'access'    => ['mautomic_crm:deals:view', 'mautomic_crm:pipelines:view'],
                'priority'  => 60,
            ],
            'mautomic_crm.deals' => [
                'route'    => 'mautic_mautomic_crm_deal_index',
                'access'   => 'mautomic_crm:deals:view',
                'parent'   => 'mautomic_crm.crm',
                'priority' => 10,
            ],
            'mautomic_crm.pipelines' => [
                'route'    => 'mautic_mautomic_crm_pipeline_index',
                'access'   => 'mautomic_crm:pipelines:view',
                'parent'   => 'mautomic_crm.crm',
                'priority' => 20,
            ],
            'mautomic_crm.tasks' => [
                'route'    => 'mautic_mautomic_crm_task_index',
                'access'   => 'mautomic_crm:tasks:view',
                'parent'   => 'mautomic_crm.crm',
                'priority' => 30,
            ],
        ],
    ],

    'categories' => [
        'plugin:mautomicCrm' => [
            'label' => 'mautomic_crm.deals',
            'class' => MauticPlugin\MautomicCrmBundle\Entity\Deal::class,
        ],
    ],
];
