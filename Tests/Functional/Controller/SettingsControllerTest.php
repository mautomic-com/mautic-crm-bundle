<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SettingsControllerTest extends MauticMysqlTestCase
{
    public function testSettingsIndexRedirectsToDealFields(): void
    {
        $this->client->followRedirects(true);
        $crawler = $this->client->request(Request::METHOD_GET, '/s/mautomic-crm/settings');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('deal-fields', (string) $this->client->getRequest()->getUri());
    }

    public function testSettingsRedirectFollowsToDealFieldsList(): void
    {
        $this->client->followRedirects(true);
        $this->client->request(Request::METHOD_GET, '/s/mautomic-crm/settings');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
}
