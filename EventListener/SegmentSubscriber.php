<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\EventListener;

use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\Event\SegmentDictionaryGenerationEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Provider\TypeOperatorProviderInterface;
use Mautic\LeadBundle\Segment\Query\Filter\ForeignValueFilterQueryBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SegmentSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TypeOperatorProviderInterface $typeOperatorProvider,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return array<string, array<int, array<int, int|string>>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE => [
                ['onGenerateSegmentFilters', -10],
            ],
            LeadEvents::SEGMENT_DICTIONARY_ON_GENERATE => [
                ['onSegmentDictionaryGenerate', 0],
            ],
        ];
    }

    public function onGenerateSegmentFilters(LeadListFiltersChoicesEvent $event): void
    {
        if (!$event->isForSegmentation()) {
            return;
        }

        $event->addChoice('lead', 'deal_pipeline', [
            'label'      => $this->translator->trans('mautomic_crm.segment.filter.deal_pipeline'),
            'properties' => ['type' => 'select'],
            'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('default'),
            'object'     => 'lead',
        ]);

        $event->addChoice('lead', 'deal_stage', [
            'label'      => $this->translator->trans('mautomic_crm.segment.filter.deal_stage'),
            'properties' => ['type' => 'select'],
            'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('default'),
            'object'     => 'lead',
        ]);

        $event->addChoice('lead', 'deal_amount', [
            'label'      => $this->translator->trans('mautomic_crm.segment.filter.deal_amount'),
            'properties' => ['type' => 'number'],
            'operators'  => $this->typeOperatorProvider->getOperatorsForFieldType('default'),
            'object'     => 'lead',
        ]);
    }

    public function onSegmentDictionaryGenerate(SegmentDictionaryGenerationEvent $event): void
    {
        $event->addTranslation('deal_pipeline', [
            'type'                => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'mautomic_deals',
            'foreign_table_field' => 'contact_id',
            'table'               => 'leads',
            'table_field'         => 'id',
            'field'               => 'pipeline_id',
            'where'               => 'mautomic_deals.is_published = 1',
        ]);

        $event->addTranslation('deal_stage', [
            'type'                => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'mautomic_deals',
            'foreign_table_field' => 'contact_id',
            'table'               => 'leads',
            'table_field'         => 'id',
            'field'               => 'stage_id',
            'where'               => 'mautomic_deals.is_published = 1',
        ]);

        $event->addTranslation('deal_amount', [
            'type'                => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'mautomic_deals',
            'foreign_table_field' => 'contact_id',
            'table'               => 'leads',
            'table_field'         => 'id',
            'field'               => 'amount',
            'where'               => 'mautomic_deals.is_published = 1',
        ]);
    }
}
