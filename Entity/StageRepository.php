<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Stage>
 */
class StageRepository extends CommonRepository
{
    public function getTableAlias(): string
    {
        return 'st';
    }

    /**
     * @return array<array<string>>
     */
    protected function getDefaultOrder(): array
    {
        return [
            [$this->getTableAlias().'.order', 'ASC'],
        ];
    }

    /**
     * @return Stage[]
     */
    public function getStagesByPipeline(int $pipelineId): array
    {
        return $this->findBy(
            ['pipeline' => $pipelineId],
            ['order' => 'ASC']
        );
    }
}
