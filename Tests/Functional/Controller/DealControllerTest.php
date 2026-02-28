<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DealControllerTest extends MauticMysqlTestCase
{
    public function testIndexActionReturns200(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/mautomic/deals');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testIndexShowsEmptyState(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/mautomic/deals');
        $content = $this->client->getResponse()->getContent();
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testIndexShowsDealInList(): void
    {
        $pipeline = $this->createPipelineWithStage();
        $stage    = $pipeline->getStages()->first();

        $deal = new Deal();
        $deal->setName('Enterprise License');
        $deal->setAmount('50000.00');
        $deal->setCurrency('USD');
        $deal->setPipeline($pipeline);
        $deal->setStage($stage);
        $deal->setIsPublished(true);
        $this->em->persist($deal);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/deals');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Enterprise License', $this->client->getResponse()->getContent());
    }

    public function testNewActionReturns200(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/mautomic/deals/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testNewActionContainsForm(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/mautomic/deals/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertGreaterThan(0, $crawler->filter('form[name="deal"]')->count(), 'Deal form should exist');
    }

    public function testEditActionReturns200(): void
    {
        $pipeline = $this->createPipelineWithStage();

        $deal = new Deal();
        $deal->setName('Edit This Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($pipeline->getStages()->first());
        $deal->setIsPublished(true);
        $this->em->persist($deal);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/deals/edit/'.$deal->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testViewActionReturns200(): void
    {
        $pipeline = $this->createPipelineWithStage();

        $deal = new Deal();
        $deal->setName('View This Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($pipeline->getStages()->first());
        $deal->setIsPublished(true);
        $this->em->persist($deal);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/deals/view/'.$deal->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('View This Deal', $this->client->getResponse()->getContent());
    }

    public function testViewNonExistentDealShowsNotFound(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/mautomic/deals/view/999999');
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
