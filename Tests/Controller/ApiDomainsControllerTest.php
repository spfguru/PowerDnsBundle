<?php
/**
 * This file is part of the SysEleven PowerDnsBundle.
 *
 * (c) SysEleven GmbH <http://www.syseleven.de/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author   M. Seifert <m.seifert@syseleven.de>
 * @package SysEleven\PowerDnsBundle\Tests\Controller
 */

namespace SysEleven\PowerDnsBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests the functionality of the Domains Controller
 *
 * @author  M. Seifert <m.seifert@syseleven.de>
 * @package SysEleven\PowerDnsBundle\Tests
 */
class ApiDomainsControllerTest extends WebTestCase
{
    
    public $urlPrefix = '';

    public function testIndex()
    {
        $client = static::createClient();
        $client->request('GET', $this->urlPrefix.'/api/domains.json',array('name' => 'foo.de'));

        $cnt = $client->getResponse()->getContent();

        $cnt = json_decode($cnt, true);

        $this->assertArrayHasKey('status',$cnt);
        $this->assertArrayHasKey('data',$cnt);
        $this->assertEquals('success', $cnt['status']);
        $this->assertCount(1, $cnt['data']);

        $client->request('GET', $this->urlPrefix.'/api/domains.json',array('search' => 'foo'));

        $cnt = $client->getResponse()->getContent();

        $cnt = json_decode($cnt, true);

        $this->assertArrayHasKey('status',$cnt);
        $this->assertArrayHasKey('data',$cnt);
        $this->assertEquals('success', $cnt['status']);
        $this->assertCount(2, $cnt['data']);

        $client->request('GET', $this->urlPrefix.'/api/domains.json',array('type' => array('MASTER')));

        $cnt = $client->getResponse()->getContent();

        $cnt = json_decode($cnt, true);

        $this->assertArrayHasKey('status',$cnt);
        $this->assertArrayHasKey('data',$cnt);
        $this->assertEquals('success', $cnt['status']);
        $this->assertCount(1, $cnt['data']);
    }

    /**
     * Tests the creation of a new domain [POST] /api/domains.json.
     */
    public function testPostDomains()
    {
        $client = static::createClient();
        $client->followRedirects(true);
        $client->request('POST', $this->urlPrefix.'/api/domains.json',array('name' => 'domain.de','type' => 'NATIVE'));

        $cnt = $client->getResponse()->getContent();
        $cnt = json_decode($cnt, true);

        $this->assertArrayHasKey('status',$cnt);
        $this->assertArrayHasKey('data',$cnt);
        $this->assertEquals('success', $cnt['status']);

        $data = $cnt['data'];
        $this->assertArrayHasKey('id',$data);
        $this->assertArrayHasKey('name',$data);
        $this->assertArrayHasKey('type',$data);
        $this->assertEquals('domain.de',$data['name']);
        $this->assertEquals('NATIVE',$data['type']);

        $client->request('POST', $this->urlPrefix.'/api/domains.json',array('name' => 'domain.de','type' => 'BOGUS'));
        $cnt = $client->getResponse()->getContent();
        $cnt = json_decode($cnt, true);

        $this->assertArrayHasKey('status',$cnt);
        $this->assertArrayHasKey('errors',$cnt);
        $this->assertEquals('error', $cnt['status']);

        $errors = $cnt['errors'];
        $this->assertCount(2, $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertEquals('A zone with the same name already exists', $errors['name']);
        $this->assertArrayHasKey('type', $errors);
        $this->assertEquals('Type not supported', $errors['type']);


        $client->request('POST', $this->urlPrefix.'/api/domains.json',array('name' => 'd','type' => 'BOGUS'));
        $cnt = $client->getResponse()->getContent();
        $cnt = json_decode($cnt, true);

        $this->assertArrayHasKey('status',$cnt);
        $this->assertArrayHasKey('errors',$cnt);
        $this->assertEquals('error', $cnt['status']);

        $errors = $cnt['errors'];
        $this->assertCount(2, $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertEquals('Please provide a zone name that is at least 2 long', $errors['name']);
        $this->assertArrayHasKey('type', $errors);
        $this->assertEquals('Type not supported', $errors['type']);


        $name = 'verylongname'.md5('verylongname')
            .md5('verylongname')
            .md5('verylongname')
            .md5('verylongname')
            .md5('verylongname')
            .md5('verylongname')
            .md5('verylongname')
            .md5('verylongname')
            .md5('verylongname')
            .md5('verylongname');

        $client->request('POST', $this->urlPrefix.'/api/domains.json',array('name' => $name,'type' => 'BOGUS'));
        $cnt = $client->getResponse()->getContent();
        $cnt = json_decode($cnt, true);

        $this->assertArrayHasKey('status',$cnt);
        $this->assertArrayHasKey('errors',$cnt);
        $this->assertEquals('error', $cnt['status']);

        $errors = $cnt['errors'];
        $this->assertCount(2, $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertEquals('The name of the zone cannot exceed 255 in length', $errors['name']);
        $this->assertArrayHasKey('type', $errors);
        $this->assertEquals('Type not supported', $errors['type']);

        return $data['id'];
    }

    /**
     * @depends testPostDomains
     * @param $id
     */
    public function testApiGetDomains($id)
    {
        $client = static::createClient();
        $client->followRedirects(true);
        $client->request('GET', $this->urlPrefix.'/api/domains/'.$id.'.json');

        $cnt = $client->getResponse()->getContent();
        $cnt = json_decode($cnt, true);

        $this->assertArrayHasKey('status',$cnt);
        $this->assertArrayHasKey('data',$cnt);
        $this->assertEquals('success', $cnt['status']);

        $client = static::createClient();
        $client->followRedirects(true);
        $client->request('GET', $this->urlPrefix.'/api/domains/999999999.json');

        $cnt = $client->getResponse()->getContent();
        $cnt = json_decode($cnt, true);

        $this->assertArrayHasKey('status',$cnt);
        $this->assertArrayHasKey('errors',$cnt);
        $this->assertEquals('error', $cnt['status']);
        $this->assertArrayHasKey('id',$cnt['errors']);
        $this->assertEquals('Not found',$cnt['errors']['id']);

        return $id;
    }

    /**
     * @depends testApiGetDomains
     * @param $id
     */
    public function testApiPutDomains($id)
    {
        $client = static::createClient();
        $client->followRedirects(true);
        $client->request('PUT', $this->urlPrefix.'/api/domains/9999999.json', array('name' => 'domain2.de','type' => 'BOGUS'));

        $cnt = $client->getResponse()->getContent();
        $cnt = json_decode($cnt, true);

        $this->assertArrayHasKey('status',$cnt);
        $this->assertArrayHasKey('errors',$cnt);
        $this->assertEquals('error', $cnt['status']);

        $client = static::createClient();
        $client->followRedirects(true);
        $client->request('PUT', $this->urlPrefix.'/api/domains/'.$id.'.json', array('name' => 'domain2.de','type' => 'NATIVE'));

        $cnt = $client->getResponse()->getContent();
        $cnt = json_decode($cnt, true);

        $this->assertArrayHasKey('status',$cnt);
        $this->assertArrayHasKey('data',$cnt);
        $this->assertEquals('success', $cnt['status']);

        $data = $cnt['data'];
        $this->assertEquals('domain2.de', $data['name']);
        $this->assertEquals('NATIVE', $data['type']);

        $client = static::createClient();
        $client->followRedirects(true);
        $client->request('PUT', $this->urlPrefix.'/api/domains/'.$id.'.json', array('name' => 'domain2.de','type' => 'BOGUS'));

        $cnt = $client->getResponse()->getContent();
        $cnt = json_decode($cnt, true);

        $this->assertArrayHasKey('status',$cnt);
        $this->assertArrayHasKey('errors',$cnt);
        $this->assertEquals('error', $cnt['status']);

        $client = static::createClient();
        $client->followRedirects(true);
        $client->request('PUT', $this->urlPrefix.'/api/domains/'.$id.'.json', array('name' => 'domain.de','type' => 'NATIVE'));

        $cnt = $client->getResponse()->getContent();
        $cnt = json_decode($cnt, true);

        $this->assertArrayHasKey('status',$cnt);
        $this->assertArrayHasKey('data',$cnt);
        $this->assertEquals('success', $cnt['status']);

        $data = $cnt['data'];
        $this->assertEquals('domain.de', $data['name']);
        $this->assertEquals('NATIVE', $data['type']);

        return $id;
    }

    /**
     * @depends testApiPutDomains
     * @param $id
     */
    public function testApiDeleteDomains($id)
    {
        $client = static::createClient();
        $client->followRedirects(true);
        $client->request('DELETE', $this->urlPrefix.'/api/domains/9999999.json');

        $cnt = $client->getResponse()->getContent();
        $cnt = json_decode($cnt, true);

        $this->assertArrayHasKey('status',$cnt);
        $this->assertArrayHasKey('errors',$cnt);
        $this->assertEquals('error', $cnt['status']);


        $client = static::createClient();
        $client->followRedirects(true);
        $client->request('DELETE', $this->urlPrefix.'/api/domains/'.$id.'.json');

        $cnt = $client->getResponse()->getContent();
        $cnt = json_decode($cnt, true);

        $this->assertArrayHasKey('status',$cnt);
        $this->assertArrayHasKey('data',$cnt);
        $this->assertEquals('success', $cnt['status']);
    }

    public function testHistory()
    {
        $client = static::createClient();
        $client->followRedirects(true);
        $client->request('GET', $this->urlPrefix.'/api/domains/1/history.json');

        $cnt = $client->getResponse()->getContent();
        $cnt = json_decode($cnt, true);

        $this->assertArrayHasKey('status',$cnt);
        $this->assertArrayHasKey('data',$cnt);
        $this->assertEquals('success', $cnt['status']);

        $client = static::createClient();
        $client->followRedirects(true);
        $client->request('GET', $this->urlPrefix.'/api/domains/999999999/history.json');

        $cnt = $client->getResponse()->getContent();
        $cnt = json_decode($cnt, true);

        $this->assertArrayHasKey('status',$cnt);
        $this->assertArrayHasKey('errors',$cnt);
        $this->assertEquals('error', $cnt['status']);
        $this->assertArrayHasKey('id',$cnt['errors']);
        $this->assertEquals('Not found',$cnt['errors']['id']);
    }
}
 