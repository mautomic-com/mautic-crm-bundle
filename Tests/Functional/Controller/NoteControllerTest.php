<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\Note;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NoteControllerTest extends MauticMysqlTestCase
{
    public function testCreateNoteLinkedToDeal(): void
    {
        $pipeline = $this->createPipelineWithStage();
        $stage    = $pipeline->getStages()->first();

        $deal = new Deal();
        $deal->setName('Note Test Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($stage);
        $deal->setIsPublished(true);
        $this->em->persist($deal);
        $this->em->flush();

        $note = new Note();
        $note->setText('Important meeting notes');
        $note->setType('meeting');
        $note->setDeal($deal);
        $note->setIsPublished(true);
        $this->em->persist($note);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/deals/view/'.$deal->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Important meeting notes', $this->client->getResponse()->getContent());
    }

    public function testNoteShowsOnDealTimelineWithIcon(): void
    {
        $pipeline = $this->createPipelineWithStage();
        $stage    = $pipeline->getStages()->first();

        $deal = new Deal();
        $deal->setName('Icon Test Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($stage);
        $deal->setIsPublished(true);
        $this->em->persist($deal);
        $this->em->flush();

        $note = new Note();
        $note->setText('Call with the client');
        $note->setType('call');
        $note->setDeal($deal);
        $note->setIsPublished(true);
        $this->em->persist($note);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/mautomic/deals/view/'.$deal->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertGreaterThan(0, $crawler->filter('.ri-phone-line')->count(), 'Call note should show phone icon');
    }

    public function testEditNoteRedirectsBackToDeal(): void
    {
        $pipeline = $this->createPipelineWithStage();
        $stage    = $pipeline->getStages()->first();

        $deal = new Deal();
        $deal->setName('Edit Note Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($stage);
        $deal->setIsPublished(true);
        $this->em->persist($deal);
        $this->em->flush();

        $note = new Note();
        $note->setText('Original text');
        $note->setType('general');
        $note->setDeal($deal);
        $note->setIsPublished(true);
        $this->em->persist($note);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/notes/edit/'.$note->getId().'?dealId='.$deal->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Original text', $this->client->getResponse()->getContent());
    }

    public function testDeleteNoteFromDeal(): void
    {
        $pipeline = $this->createPipelineWithStage();
        $stage    = $pipeline->getStages()->first();

        $deal = new Deal();
        $deal->setName('Delete Note Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($stage);
        $deal->setIsPublished(true);
        $this->em->persist($deal);
        $this->em->flush();

        $note = new Note();
        $note->setText('Note to be deleted');
        $note->setType('email');
        $note->setDeal($deal);
        $note->setIsPublished(true);
        $this->em->persist($note);
        $this->em->flush();

        $noteId = $note->getId();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/notes/delete/'.$noteId.'?dealId='.$deal->getId());

        $this->assertNull($this->em->find(Note::class, $noteId));
    }

    public function testNewNoteFormReturns200(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/mautomic/notes/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
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
