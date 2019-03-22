<?php
// tests/Controller/LibraryControllerTest.php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiControllerTest extends WebTestCase
{
    public function testApiAddBook()
    {
        $client = $this->createClient();

        $date = new \DateTime('now');
        $date = $date->format('Y-m-d H:i:s');

        $data = [
            'apiKey' => '12345',
            'name' => 'test book',
            'author' => 'test author',
            'date' => $date,
            'allow' => 1,
        ];

        $crawler = $client->request('GET', '/api/v1/books/add', $data);
        
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
