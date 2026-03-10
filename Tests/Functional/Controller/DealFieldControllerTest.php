<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MautomicCrmBundle\Entity\DealField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DealFieldControllerTest extends MauticMysqlTestCase
{
    public function testDealFieldListPage(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/mautomic-crm/settings/deal-fields');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateDealField(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/mautomic-crm/settings/deal-fields/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Save')->form([
            'deal_field[label]'       => 'Contract Number',
            'deal_field[alias]'       => 'contract_number',
            'deal_field[type]'        => 'text',
            'deal_field[isPublished]' => '1',
        ]);

        $this->client->submit($form);

        $field = $this->em->getRepository(DealField::class)->findOneBy(['alias' => 'contract_number']);
        $this->assertNotNull($field);
        $this->assertSame('Contract Number', $field->getLabel());
        $this->assertSame('text', $field->getType());
    }

    public function testEditDealField(): void
    {
        $field = new DealField();
        $field->setLabel('Old Label');
        $field->setAlias('old_label');
        $field->setType('text');
        $field->setIsPublished(true);
        $this->em->persist($field);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/mautomic-crm/settings/deal-fields/edit/'.$field->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Save')->form([
            'deal_field[label]' => 'Updated Label',
        ]);

        $this->client->submit($form);

        $this->em->clear();
        $updated = $this->em->getRepository(DealField::class)->find($field->getId());
        $this->assertSame('Updated Label', $updated->getLabel());
    }

    public function testDeleteDealField(): void
    {
        $field = new DealField();
        $field->setLabel('To Delete');
        $field->setAlias('to_delete');
        $field->setType('text');
        $field->setIsPublished(true);
        $this->em->persist($field);
        $this->em->flush();

        $fieldId = $field->getId();

        $this->client->request(Request::METHOD_POST, '/s/mautomic-crm/settings/deal-fields/delete/'.$fieldId);

        $this->em->clear();
        $deleted = $this->em->getRepository(DealField::class)->find($fieldId);
        $this->assertNull($deleted);
    }
}
