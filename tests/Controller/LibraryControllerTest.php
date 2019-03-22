<?php
// tests/Controller/LibraryControllerTest.php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\File;

class LibraryControllerTest extends WebTestCase
{
    public function testAddBook()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/login');
        $loginForm = $crawler->selectButton("_submit")->form(array(
            "_username"  => "admin",
            "_password"  => "admin",
            ));
        $client->submit($loginForm);

        $this->assertTrue($client->getResponse()->isRedirect());
        $crawler = $client->followRedirect();

        $link = $crawler->filter('#add_btn');
        $this->assertGreaterThan(0, $link->count());
        $crawler = $client->click($link->link());

        $temp = $crawler->filter('.form_header');

        $date = new \DateTime('now');
        $date = $date->format('Y-m-d H:i:s');

        $bookAddForm = $crawler
            ->selectButton('form[submit]')
            ->form([
                'form[name]' => 'test book',
                'form[author]' => 'test author',
                'form[date]' => $date,
                'form[cover]' => new File('public/uploads/test_data/test.jpg'),
                'form[file]' => new File('public/uploads/test_data/test.txt'),
            ]);
        $bookAddForm['form[allow]']->tick();
        $client->submit($bookAddForm);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
