<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\Note;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use MauticPlugin\MautomicCrmBundle\Entity\Task;

class PipelinePersistenceTest extends MauticMysqlTestCase
{
    public function testCreateAndRetrievePipeline(): void
    {
        $pipeline = new Pipeline();
        $pipeline->setName('E-Commerce Pipeline');
        $pipeline->setDescription('For online sales');
        $pipeline->setIsDefault(true);
        $pipeline->setIsPublished(true);

        $this->em->persist($pipeline);
        $this->em->flush();
        $this->em->clear();

        $loaded = $this->em->find(Pipeline::class, $pipeline->getId());
        $this->assertNotNull($loaded);
        $this->assertSame('E-Commerce Pipeline', $loaded->getName());
        $this->assertSame('For online sales', $loaded->getDescription());
        $this->assertTrue($loaded->getIsDefault());
    }

    public function testCreatePipelineWithStages(): void
    {
        $pipeline = new Pipeline();
        $pipeline->setName('Sales Pipeline');
        $pipeline->setIsPublished(true);
        $this->em->persist($pipeline);

        $stage1 = new Stage();
        $stage1->setName('Prospecting');
        $stage1->setPipeline($pipeline);
        $stage1->setOrder(1);
        $stage1->setProbability(10);
        $stage1->setType('open');
        $this->em->persist($stage1);

        $stage2 = new Stage();
        $stage2->setName('Closed Won');
        $stage2->setPipeline($pipeline);
        $stage2->setOrder(2);
        $stage2->setProbability(100);
        $stage2->setType('won');
        $this->em->persist($stage2);

        $this->em->flush();
        $this->em->clear();

        $loaded = $this->em->find(Pipeline::class, $pipeline->getId());
        $this->assertNotNull($loaded);
        $this->assertCount(2, $loaded->getStages());
    }

    public function testCreateDealWithRelationships(): void
    {
        $pipeline = new Pipeline();
        $pipeline->setName('Deal Pipeline');
        $pipeline->setIsPublished(true);
        $this->em->persist($pipeline);

        $stage = new Stage();
        $stage->setName('Negotiation');
        $stage->setPipeline($pipeline);
        $stage->setOrder(1);
        $stage->setProbability(75);
        $this->em->persist($stage);

        $this->em->flush();

        $deal = new Deal();
        $deal->setName('Big Enterprise Deal');
        $deal->setAmount('100000.00');
        $deal->setCurrency('EUR');
        $deal->setCloseDate(new \DateTime('2026-06-30'));
        $deal->setPipeline($pipeline);
        $deal->setStage($stage);
        $deal->setIsPublished(true);
        $this->em->persist($deal);
        $this->em->flush();
        $this->em->clear();

        $loaded = $this->em->find(Deal::class, $deal->getId());
        $this->assertNotNull($loaded);
        $this->assertSame('Big Enterprise Deal', $loaded->getName());
        $this->assertSame('100000.00', $loaded->getAmount());
        $this->assertSame('EUR', $loaded->getCurrency());
        $this->assertNotNull($loaded->getPipeline());
        $this->assertSame('Deal Pipeline', $loaded->getPipeline()->getName());
        $this->assertNotNull($loaded->getStage());
        $this->assertSame('Negotiation', $loaded->getStage()->getName());
    }

    public function testCreateTask(): void
    {
        $task = new Task();
        $task->setTitle('Follow up with lead');
        $task->setDescription('Call them about the proposal');
        $task->setPriority('high');
        $task->setStatus('open');
        $task->setDueDate(new \DateTime('2026-03-15'));
        $task->setIsPublished(true);
        $this->em->persist($task);
        $this->em->flush();
        $this->em->clear();

        $loaded = $this->em->find(Task::class, $task->getId());
        $this->assertNotNull($loaded);
        $this->assertSame('Follow up with lead', $loaded->getTitle());
        $this->assertSame('high', $loaded->getPriority());
        $this->assertSame('open', $loaded->getStatus());
    }

    public function testCreateNote(): void
    {
        $note = new Note();
        $note->setText('Had a great discovery call');
        $note->setType('call');
        $note->setIsPublished(true);
        $this->em->persist($note);
        $this->em->flush();
        $this->em->clear();

        $loaded = $this->em->find(Note::class, $note->getId());
        $this->assertNotNull($loaded);
        $this->assertSame('Had a great discovery call', $loaded->getText());
        $this->assertSame('call', $loaded->getType());
    }

    public function testTaskLinkedToDeal(): void
    {
        $pipeline = new Pipeline();
        $pipeline->setName('Pipeline');
        $pipeline->setIsPublished(true);
        $this->em->persist($pipeline);

        $stage = new Stage();
        $stage->setName('Open');
        $stage->setPipeline($pipeline);
        $stage->setOrder(1);
        $this->em->persist($stage);

        $deal = new Deal();
        $deal->setName('Linked Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($stage);
        $deal->setIsPublished(true);
        $this->em->persist($deal);

        $task = new Task();
        $task->setTitle('Task for deal');
        $task->setDeal($deal);
        $task->setIsPublished(true);
        $this->em->persist($task);

        $this->em->flush();
        $this->em->clear();

        $loaded = $this->em->find(Task::class, $task->getId());
        $this->assertNotNull($loaded);
        $this->assertNotNull($loaded->getDeal());
        $this->assertSame('Linked Deal', $loaded->getDeal()->getName());
    }

    public function testNoteLinkedToDeal(): void
    {
        $pipeline = new Pipeline();
        $pipeline->setName('Pipeline');
        $pipeline->setIsPublished(true);
        $this->em->persist($pipeline);

        $stage = new Stage();
        $stage->setName('Open');
        $stage->setPipeline($pipeline);
        $stage->setOrder(1);
        $this->em->persist($stage);

        $deal = new Deal();
        $deal->setName('Noted Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($stage);
        $deal->setIsPublished(true);
        $this->em->persist($deal);

        $note = new Note();
        $note->setText('Meeting notes from discovery');
        $note->setType('meeting');
        $note->setDeal($deal);
        $note->setIsPublished(true);
        $this->em->persist($note);

        $this->em->flush();
        $this->em->clear();

        $loaded = $this->em->find(Note::class, $note->getId());
        $this->assertNotNull($loaded);
        $this->assertNotNull($loaded->getDeal());
        $this->assertSame('Noted Deal', $loaded->getDeal()->getName());
    }
}
