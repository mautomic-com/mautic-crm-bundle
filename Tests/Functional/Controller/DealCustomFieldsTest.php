<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MautomicCrmBundle\Entity\Deal;
use MauticPlugin\MautomicCrmBundle\Entity\DealField;
use MauticPlugin\MautomicCrmBundle\Entity\DealFieldValue;
use MauticPlugin\MautomicCrmBundle\Entity\Pipeline;
use MauticPlugin\MautomicCrmBundle\Entity\Stage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DealCustomFieldsTest extends MauticMysqlTestCase
{
    public function testDealFormIncludesCustomFields(): void
    {
        $this->createDealField('Contract Number', 'contract_number', 'text');

        $crawler = $this->client->request(Request::METHOD_GET, '/s/mautomic/deals/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('cf_contract_number', $content);
    }

    public function testDealSavesCustomFieldValues(): void
    {
        $field    = $this->createDealField('Contract Number', 'contract_number', 'text');
        $pipeline = $this->createPipelineWithStage();
        $stage    = $pipeline->getStages()->first();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/mautomic/deals/new');
        $form    = $crawler->selectButton('Save')->form([
            'deal[name]'                => 'Custom Fields Deal',
            'deal[pipeline]'            => $pipeline->getId(),
            'deal[stage]'               => $stage->getId(),
            'deal[cf_contract_number]'  => 'C-12345',
        ]);

        $this->client->submit($form);

        $deal = $this->em->getRepository(Deal::class)->findOneBy(['name' => 'Custom Fields Deal']);
        $this->assertNotNull($deal);

        $value = $this->em->getRepository(DealFieldValue::class)->findOneBy([
            'deal'  => $deal->getId(),
            'field' => $field->getId(),
        ]);
        $this->assertNotNull($value);
        $this->assertSame('C-12345', $value->getValue());
    }

    public function testDealEditPreloadsCustomFieldValues(): void
    {
        $field    = $this->createDealField('Contract Number', 'contract_number', 'text');
        $pipeline = $this->createPipelineWithStage();
        $stage    = $pipeline->getStages()->first();

        $deal = new Deal();
        $deal->setName('Preload Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($stage);
        $deal->setIsPublished(true);
        $this->em->persist($deal);
        $this->em->flush();

        $fieldValue = new DealFieldValue();
        $fieldValue->setDeal($deal);
        $fieldValue->setField($field);
        $fieldValue->setValue('C-99999');
        $this->em->persist($fieldValue);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/mautomic/deals/edit/'.$deal->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('C-99999', $content);
    }

    public function testDealDetailShowsCustomFields(): void
    {
        $field    = $this->createDealField('Contract Number', 'contract_number', 'text');
        $pipeline = $this->createPipelineWithStage();
        $stage    = $pipeline->getStages()->first();

        $deal = new Deal();
        $deal->setName('Detail View Deal');
        $deal->setPipeline($pipeline);
        $deal->setStage($stage);
        $deal->setIsPublished(true);
        $this->em->persist($deal);
        $this->em->flush();

        $fieldValue = new DealFieldValue();
        $fieldValue->setDeal($deal);
        $fieldValue->setField($field);
        $fieldValue->setValue('C-77777');
        $this->em->persist($fieldValue);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/mautomic/deals/view/'.$deal->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Contract Number', $content);
        $this->assertStringContainsString('C-77777', $content);
    }

    private function createDealField(string $label, string $alias, string $type): DealField
    {
        $field = new DealField();
        $field->setLabel($label);
        $field->setAlias($alias);
        $field->setType($type);
        $field->setIsPublished(true);
        $this->em->persist($field);
        $this->em->flush();

        return $field;
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
