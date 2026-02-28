<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use MauticPlugin\MautomicCrmBundle\Entity\Task;
use MauticPlugin\MautomicCrmBundle\Entity\TaskRepository;

class TaskRepositoryFunctionalTest extends MauticMysqlTestCase
{
    public function testFindByDealReturnsLinkedTasks(): void
    {
        $pipeline = $this->createPipelineWithStage();

        $deal = new Deal();
        $deal->setName('Repo Test Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($pipeline->getStages()->first());
        $deal->setIsPublished(true);
        $this->em->persist($deal);

        $task1 = new Task();
        $task1->setTitle('First task');
        $task1->setDeal($deal);
        $task1->setStatus('open');
        $task1->setPriority('normal');
        $task1->setDueDate(new \DateTime('+1 day'));
        $task1->setIsPublished(true);
        $this->em->persist($task1);

        $task2 = new Task();
        $task2->setTitle('Second task');
        $task2->setDeal($deal);
        $task2->setStatus('open');
        $task2->setPriority('high');
        $task2->setDueDate(new \DateTime('+2 days'));
        $task2->setIsPublished(true);
        $this->em->persist($task2);

        $unlinkedTask = new Task();
        $unlinkedTask->setTitle('Unlinked task');
        $unlinkedTask->setStatus('open');
        $unlinkedTask->setPriority('normal');
        $unlinkedTask->setIsPublished(true);
        $this->em->persist($unlinkedTask);

        $this->em->flush();

        /** @var TaskRepository $repo */
        $repo = $this->em->getRepository(Task::class);

        $tasks = $repo->findByDeal($deal->getId());

        $this->assertCount(2, $tasks);
        $titles = array_map(fn (Task $t) => $t->getTitle(), $tasks);
        $this->assertContains('First task', $titles);
        $this->assertContains('Second task', $titles);
    }

    public function testFindByDealReturnsEmptyForDealWithNoTasks(): void
    {
        $pipeline = $this->createPipelineWithStage();

        $deal = new Deal();
        $deal->setName('Empty Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($pipeline->getStages()->first());
        $deal->setIsPublished(true);
        $this->em->persist($deal);
        $this->em->flush();

        /** @var TaskRepository $repo */
        $repo = $this->em->getRepository(Task::class);

        $tasks = $repo->findByDeal($deal->getId());
        $this->assertCount(0, $tasks);
    }

    private function createPipelineWithStage(): Pipeline
    {
        $pipeline = new Pipeline();
        $pipeline->setName('Test Pipeline');
        $pipeline->setIsPublished(true);
        $this->em->persist($pipeline);

        $stage = new Stage();
        $stage->setName('Qualification');
        $stage->setPipeline($pipeline);
        $stage->setOrder(1);
        $stage->setProbability(25);
        $stage->setType('open');
        $this->em->persist($stage);

        $pipeline->addStage($stage);
        $this->em->flush();

        return $pipeline;
    }
}
