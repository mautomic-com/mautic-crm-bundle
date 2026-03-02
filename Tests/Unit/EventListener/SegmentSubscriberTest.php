<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\EventListener;

use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\Event\SegmentDictionaryGenerationEvent;
use Mautic\LeadBundle\Provider\TypeOperatorProviderInterface;
use MauticPlugin\MautomicCrmBundle\EventListener\SegmentSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class SegmentSubscriberTest extends TestCase
{
    private TypeOperatorProviderInterface&MockObject $typeOperatorProvider;

    private TranslatorInterface&MockObject $translator;

    private SegmentSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->typeOperatorProvider = $this->createMock(TypeOperatorProviderInterface::class);
        $this->typeOperatorProvider->method('getOperatorsForFieldType')->willReturn([]);

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->method('trans')->willReturnArgument(0);

        $this->subscriber = new SegmentSubscriber(
            $this->typeOperatorProvider,
            $this->translator,
        );
    }

    public function testOnGenerateSegmentFiltersRegistersThreeFilters(): void
    {
        $event = $this->createMock(LeadListFiltersChoicesEvent::class);
        $event->method('isForSegmentation')->willReturn(true);

        $event->expects($this->exactly(3))
            ->method('addChoice')
            ->willReturnCallback(function (string $object, string $alias): void {
                $this->assertSame('lead', $object);
                $this->assertContains($alias, ['deal_pipeline', 'deal_stage', 'deal_amount']);
            });

        $this->subscriber->onGenerateSegmentFilters($event);
    }

    public function testOnSegmentDictionaryGenerateRegistersThreeEntries(): void
    {
        $event = $this->createMock(SegmentDictionaryGenerationEvent::class);

        $event->expects($this->exactly(3))
            ->method('addTranslation')
            ->willReturnCallback(function (string $key, array $attributes) use ($event): SegmentDictionaryGenerationEvent {
                $this->assertContains($key, ['deal_pipeline', 'deal_stage', 'deal_amount']);
                $this->assertSame('mautomic_deals', $attributes['foreign_table']);
                $this->assertSame('contact_id', $attributes['foreign_table_field']);

                return $event;
            });

        $this->subscriber->onSegmentDictionaryGenerate($event);
    }

    public function testFiltersSkippedWhenNotSegmentation(): void
    {
        $event = $this->createMock(LeadListFiltersChoicesEvent::class);
        $event->method('isForSegmentation')->willReturn(false);

        $event->expects($this->never())->method('addChoice');

        $this->subscriber->onGenerateSegmentFilters($event);
    }
}
