<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PipelineBoardViewTest extends MauticMysqlTestCase
{
    public function testPipelineDetailShowsBoardTab(): void
    {
        $pipeline = $this->createPipelineWithStages();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/pipelines/view/'.$pipeline->getId());
        $content = $this->client->getResponse()->getContent();

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Board View', $content);
        $this->assertStringContainsString('#board-tab', $content);
    }

    public function testBoardShowsDealsInCorrectColumns(): void
    {
        $pipeline = $this->createPipelineWithStages();
        $stages   = $pipeline->getStages()->toArray();

        $deal1 = $this->createDeal($pipeline, $stages[0], 'Alpha Deal');
        $deal2 = $this->createDeal($pipeline, $stages[1], 'Beta Deal');

        $this->em->clear();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/pipelines/view/'.$pipeline->getId());
        $content = $this->client->getResponse()->getContent();

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Alpha Deal', $content);
        $this->assertStringContainsString('Beta Deal', $content);
        $this->assertStringContainsString('Qualification', $content);
        $this->assertStringContainsString('Proposal', $content);
    }

    public function testBoardShowsWonLostColors(): void
    {
        $pipeline = $this->createPipelineWithStages();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/pipelines/view/'.$pipeline->getId());
        $content = $this->client->getResponse()->getContent();

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('bg-success', $content);
        $this->assertStringContainsString('bg-danger', $content);
    }

    public function testBoardShowsDealAmountAndContact(): void
    {
        $pipeline = $this->createPipelineWithStages();
        $stages   = $pipeline->getStages()->toArray();

        $contact = new Lead();
        $contact->setFirstname('Jane');
        $contact->setLastname('Doe');
        $this->em->persist($contact);
        $this->em->flush();

        $deal = new Deal();
        $deal->setName('Contact Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($stages[0]);
        $deal->setAmount('15000.00');
        $deal->setCurrency('USD');
        $deal->setContact($contact);
        $deal->setIsPublished(true);
        $this->em->persist($deal);
        $this->em->flush();
        $this->em->clear();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/pipelines/view/'.$pipeline->getId());
        $content = $this->client->getResponse()->getContent();

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('15,000.00', $content);
        $this->assertStringContainsString('Contact Deal', $content);
    }

    public function testEmptyBoardShowsNoDealsMessage(): void
    {
        $pipeline = $this->createPipelineWithStages();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/pipelines/view/'.$pipeline->getId());
        $content = $this->client->getResponse()->getContent();

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('No deals in this stage', $content);
    }

    private function createPipelineWithStages(string $name = 'Sales Pipeline'): Pipeline
    {
        $pipeline = new Pipeline();
        $pipeline->setName($name);
        $pipeline->setIsPublished(true);
        $this->em->persist($pipeline);

        $stageData = [
            ['name' => 'Qualification', 'order' => 1, 'probability' => 25, 'type' => 'open'],
            ['name' => 'Proposal',      'order' => 2, 'probability' => 50, 'type' => 'open'],
            ['name' => 'Closed Won',    'order' => 3, 'probability' => 100, 'type' => 'won'],
            ['name' => 'Closed Lost',   'order' => 4, 'probability' => 0, 'type' => 'lost'],
        ];

        foreach ($stageData as $data) {
            $stage = new Stage();
            $stage->setName($data['name']);
            $stage->setPipeline($pipeline);
            $stage->setOrder($data['order']);
            $stage->setProbability($data['probability']);
            $stage->setType($data['type']);
            $this->em->persist($stage);
            $pipeline->addStage($stage);
        }

        $this->em->flush();

        return $pipeline;
    }

    private function createDeal(Pipeline $pipeline, Stage $stage, string $name = 'Test Deal'): Deal
    {
        $deal = new Deal();
        $deal->setName($name);
        $deal->setPipeline($pipeline);
        $deal->setStage($stage);
        $deal->setIsPublished(true);
        $this->em->persist($deal);
        $this->em->flush();

        return $deal;
    }
}
