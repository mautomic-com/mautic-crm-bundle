<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use Symfony\Component\HttpFoundation\Response;

class NoteApiControllerTest extends MauticMysqlTestCase
{
    private int $dealId;
    private int $contactId;

    protected function setUp(): void
    {
        parent::setUp();

        $pipeline = new Pipeline();
        $pipeline->setName('API Note Test Pipeline');
        $pipeline->setIsPublished(true);

        $stage = new Stage();
        $stage->setName('Prospecting');
        $stage->setOrder(1);
        $stage->setProbability(10);
        $stage->setType('open');
        $stage->setPipeline($pipeline);
        $pipeline->addStage($stage);

        $this->em->persist($pipeline);
        $this->em->persist($stage);

        $deal = new Deal();
        $deal->setName('API Note Test Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($stage);
        $deal->setIsPublished(true);
        $this->em->persist($deal);

        $contact = new Lead();
        $contact->setFirstname('Jane');
        $contact->setLastname('Smith');
        $contact->setEmail('jane.smith@example.com');
        $this->em->persist($contact);

        $this->em->flush();

        $this->dealId    = $deal->getId();
        $this->contactId = $contact->getId();
    }

    public function testCreateNoteWithDealViaApi(): void
    {
        $payload = [
            'text'        => 'Call notes from meeting',
            'type'        => 'call',
            'deal'        => $this->dealId,
            'isPublished' => true,
        ];

        $this->client->request('POST', '/api/mautomic/notes/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertArrayHasKey('note', $response);
        $this->assertSame('Call notes from meeting', $response['note']['text']);
        $this->assertSame($this->dealId, $response['note']['deal']['id']);
    }

    public function testCreateNoteWithContactViaApi(): void
    {
        $payload = [
            'text'        => 'Email follow-up with contact',
            'type'        => 'email',
            'contact'     => $this->contactId,
            'isPublished' => true,
        ];

        $this->client->request('POST', '/api/mautomic/notes/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertArrayHasKey('note', $response);
        $this->assertSame('Email follow-up with contact', $response['note']['text']);
        $this->assertSame($this->contactId, $response['note']['contact']['id']);
    }

    public function testGetNoteIncludesDealAndContact(): void
    {
        $payload = [
            'text'        => 'Full linking note test',
            'type'        => 'general',
            'deal'        => $this->dealId,
            'contact'     => $this->contactId,
            'isPublished' => true,
        ];

        $this->client->request('POST', '/api/mautomic/notes/new', $payload);
        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $noteId         = $createResponse['note']['id'];

        $this->client->request('GET', "/api/mautomic/notes/{$noteId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $this->assertSame($noteId, $response['note']['id']);
        $this->assertSame($this->dealId, $response['note']['deal']['id']);
        $this->assertSame($this->contactId, $response['note']['contact']['id']);
    }
}
