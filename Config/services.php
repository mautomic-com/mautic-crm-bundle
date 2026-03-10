<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [];

    $services->load('MauticPlugin\\MautomicCrmBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('MauticPlugin\\MautomicCrmBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);

    $services->alias('mautic.mautomic_crm.model.pipeline', MauticPlugin\MautomicCrmBundle\Model\PipelineModel::class)->public();
    $services->alias('mautic.mautomic_crm.model.deal', MauticPlugin\MautomicCrmBundle\Model\DealModel::class)->public();
    $services->alias('mautic.mautomic_crm.model.task', MauticPlugin\MautomicCrmBundle\Model\TaskModel::class)->public();
    $services->alias('mautic.mautomic_crm.model.note', MauticPlugin\MautomicCrmBundle\Model\NoteModel::class)->public();
    $services->alias('mautic.mautomic_crm.model.deal_field', MauticPlugin\MautomicCrmBundle\Model\DealFieldModel::class)->public();
    $services->alias('mautic.mautomic_crm.model.task_queue', MauticPlugin\MautomicCrmBundle\Model\TaskQueueModel::class)->public();
    $services->alias('mautic.mautomic_crm.model.forecast', MauticPlugin\MautomicCrmBundle\Model\ForecastModel::class)->public();
};
