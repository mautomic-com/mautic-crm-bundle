<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\DealRepository;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use MauticPlugin\MautomicCrmBundle\Entity\StageRepository;
use MauticPlugin\MautomicCrmBundle\Model\DealModel;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DealModelStageResetTest extends TestCase
{
    private DealModel $model;

    /** @var StageRepository&\PHPUnit\Framework\MockObject\MockObject */
    private StageRepository $stageRepository;

    /** @var DealRepository&\PHPUnit\Framework\MockObject\MockObject */
    private DealRepository $dealRepository;

    protected function setUp(): void
    {
        $this->stageRepository = $this->createMock(StageRepository::class);
        $this->dealRepository  = $this->createMock(DealRepository::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')
            ->willReturnCallback(function (string $class) {
                if (Stage::class === $class) {
                    return $this->stageRepository;
                }

                if (Deal::class === $class) {
                    return $this->dealRepository;
                }

                return $this->createMock(\Doctrine\ORM\EntityRepository::class);
            });

        $em->method('getClassMetadata')
            ->willReturn($this->createConfiguredMock(\Doctrine\ORM\Mapping\ClassMetadata::class, [
                'getIdentifierValues' => [1],
            ]));

        $dispatcher           = $this->createMock(EventDispatcherInterface::class);
        $router               = $this->createMock(UrlGeneratorInterface::class);
        $translator           = $this->createMock(Translator::class);
        $userHelper           = $this->createMock(UserHelper::class);
        $logger               = $this->createMock(LoggerInterface::class);
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $security             = $this->createMock(CorePermissions::class);

        $userHelper->method('getUser')->willReturn(new User());

        $this->model = new DealModel(
            $em,
            $security,
            $dispatcher,
            $router,
            $translator,
            $userHelper,
            $logger,
            $coreParametersHelper,
        );
    }

    public function testPipelineChangeResetsStageToFirst(): void
    {
        $oldPipeline = new Pipeline();
        $oldPipeline->setName('Old Pipeline');

        $newPipeline = $this->createConfiguredMock(Pipeline::class, [
            'getId' => 2,
        ]);

        $oldStage = new Stage();
        $oldStage->setName('Old Stage');

        $newFirstStage = new Stage();
        $newFirstStage->setName('New First Stage');

        $deal = new Deal();
        $deal->setName('Test Deal');
        $deal->setPipeline($oldPipeline);
        $deal->setStage($oldStage);

        // Simulate pipeline change — sets the 'pipeline' key in changes
        $deal->setPipeline($newPipeline);

        $this->stageRepository->expects($this->once())
            ->method('getStagesByPipeline')
            ->with(2)
            ->willReturn([$newFirstStage]);

        $this->model->saveEntity($deal);

        $this->assertSame($newFirstStage, $deal->getStage());
    }

    public function testSavingWithoutPipelineChangeKeepsStage(): void
    {
        $pipeline = $this->createConfiguredMock(Pipeline::class, [
            'getId' => 1,
        ]);

        $currentStage = new Stage();
        $currentStage->setName('Current Stage');

        $deal = new Deal();
        $deal->setName('Test Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($currentStage);

        // Reset changes to simulate entity loaded fresh from DB
        $deal->resetChanges();

        // Simulate some other change, not pipeline
        $deal->setName('Updated Name');

        $this->stageRepository->expects($this->never())
            ->method('getStagesByPipeline');

        $this->model->saveEntity($deal);

        $this->assertSame($currentStage, $deal->getStage());
    }
}
