<?php

namespace App\Tests;

use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;


class ApiTest extends WebTestCase
{

    /**
     * @var Client
     */
    protected $client;

    protected function setUp()
    {
        parent::setUp();

        $this->client = static::createClient();
    }
    protected function request(string $method, string $uri, $content = null, array $headers = []): Response
    {
        $uri = "/api$uri";
        $server = ['CONTENT_TYPE' => 'application/ld+json', 'HTTP_ACCEPT' => 'application/ld+json'];
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'content-type') {
                $server['CONTENT_TYPE'] = $value;

                continue;
            }

            $server['HTTP_'.strtoupper(str_replace('-', '_', $key))] = $value;
        }

        if (is_array($content) && false !== preg_match('#^application/(?:.+\+)?json$#', $server['CONTENT_TYPE'])) {
            $content = json_encode($content);
        }

        $this->client->request($method, $uri, [], [], $server, $content);

        return $this->client->getResponse();
    }

    public function testReceiveTheBookList(): void
    {
        $response = $this->request('GET', '/books');
        $json = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/ld+json; charset=utf-8', $response->headers->get('Content-Type'));

        $this->assertArrayHasKey('hydra:member', $json);
        $this->assertEquals(10, $json['hydra:totalItems']);

        $this->assertArrayHasKey('hydra:member', $json);
        $this->assertCount(10, $json['hydra:member']);
    }


    public function testWillThowAnErrorWhenDataInValid()
    {
        $response = $this->request('POST', '/books',[
          "isbn"=> "123",
          "title"=> "",
          "author"=> "string",
          "publicationDate"=> "2019-03-27"
        ]);
        $json = json_decode($response->getContent(), true);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/ld+json; charset=utf-8', $response->headers->get('Content-Type'));

        $this->assertEquals('An error occurred', $json['hydra:title']);
        $this->assertArrayHasKey('violations', $json);
        $this->assertCount(2, $json['violations']);

        $this->assertArrayHasKey('propertyPath', $json['violations'][0]);
        $this->assertEquals('isbn', $json['violations'][0]['propertyPath']);

        $this->assertArrayHasKey('propertyPath', $json['violations'][1]);
        $this->assertEquals('title', $json['violations'][1]['propertyPath']);
    }


}