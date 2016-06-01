<?php

namespace AppBundle\Action\Admin;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient(array(), array(
            'PHP_AUTH_USER' => 'ayso1sra@gmail.com',
            'PHP_AUTH_PW'   => 'ayso1sra@gmail.com',
        )); 

        $crawler = $client->request('GET', '/admin', array(), array(), array(
            'PHP_AUTH_USER' => 'username',
            'PHP_AUTH_PW'   => 'pa$$word',
        ));

        $this->assertEquals(302, $client->getResponse()->getStatusCode());

    }
}
