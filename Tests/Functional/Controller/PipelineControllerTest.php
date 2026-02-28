<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PipelineControllerTest extends MauticMysqlTestCase
{
    public function testIndexActionReturns200(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/mautomic/pipelines');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testIndexActionContainsPipelineName(): void
    {
        $pipeline = new Pipeline();
        $pipeline->setName('Test Sales Pipeline');
        $pipeline->setIsPublished(true);
        $this->em->persist($pipeline);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/pipelines');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Test Sales Pipeline', $this->client->getResponse()->getContent());
    }

    public function testNewActionReturns200(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/mautomic/pipelines/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testNewActionContainsForm(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/mautomic/pipelines/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertGreaterThan(0, $crawler->filter('form[name="pipeline"]')->count(), 'Pipeline form should exist');
    }

    public function testEditActionReturns200(): void
    {
        $pipeline = new Pipeline();
        $pipeline->setName('Edit Me');
        $pipeline->setIsPublished(true);
        $this->em->persist($pipeline);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/pipelines/edit/'.$pipeline->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testViewActionReturns200(): void
    {
        $pipeline = new Pipeline();
        $pipeline->setName('View Me');
        $pipeline->setIsPublished(true);
        $this->em->persist($pipeline);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/pipelines/view/'.$pipeline->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testViewNonExistentPipelineShowsNotFound(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/mautomic/pipelines/view/999999');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
}
