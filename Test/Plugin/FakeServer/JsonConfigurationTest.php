<?php

namespace Ant\Bundle\GuzzleFakeServer\Test\Plugin\FakeServer;

use Ant\Bundle\GuzzleFakeServer\Guzzle\Plugin\FakeServer\ConfigurationInterface;
use Ant\Bundle\GuzzleFakeServer\Guzzle\Plugin\FakeServer\JsonConfiguration;

class JsonConfigurationTest extends \PHPUnit_Framework_TestCase
{
    private $jsonConfiguration;

    public function setUp()
    {
        parent::setUp();
        $jsonConfigFile = __DIR__.'/fixtures/urlMappings.json';
        $this->jsonConfiguration = new JsonConfiguration("http://misuperhost.com", $jsonConfigFile);
    }

    public function testImplementsConfigurationInterface()
    {
        $this->assertTrue($this->jsonConfiguration instanceof ConfigurationInterface);
    }

    public function testGetResourceMappings()
    {
        $expected = array(
            array(
                'url' => 'http://misuperhost.com/fake',
                'method' => 'GET',
                'response' => array(
                    'status' => 200,
                    'resource' => 'fixtures/fake_get.json'
                    )
                ),
            array(
                'url' => 'http://misuperhost.com/fake2',
                'method' => 'POST',
                'response' => array(
                    'status' => 201,
                    'resource' => 'fixtures/fake_post.json'
                    )
                ),
            );

        $this->assertEquals($expected, $this->jsonConfiguration->getResourceMappings());
    }
}