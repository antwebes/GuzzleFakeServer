<?php

namespace Ant\Bundle\GuzzleFakeServer\Test\Plugin\FakeServer;

use Ant\Bundle\GuzzleFakeServer\Guzzle\Plugin\FakeServer\FakeServer;
use Guzzle\Http\Client;

class FakeServerTest extends \PHPUnit_Framework_TestCase
{
    private $configurationInterface;
    private $resourceLoaderInterface;
    private $fakeServer;

    public function setUp()
    {
        parent::setUp();

        $this->configurationInterface = $this->getMockForAbstractClass('Ant\Bundle\GuzzleFakeServer\Guzzle\Plugin\FakeServer\ConfigurationInterface');
        $this->resourceLoaderInterface = $this->getMockForAbstractClass('Ant\Bundle\GuzzleFakeServer\Guzzle\Plugin\FakeServer\ResourceLoaderInterface');

        $resourceMap = array(
            'fixtures/fake_get.json' => 'get fake response',
            'fixtures/fake_post.json' => 'post fake response',
            'fixtures/fake_post_with_param.json' => 'post fake response with params',
            'fixtures/fake_post_with_recursive_param.json' => 'post fake response with recursive params'
            );

        $loadResource = function($resource) use ($resourceMap){
            return $resourceMap[$resource];
        };

        $this->configurationInterface
            ->expects($this->any())
            ->method('getResourceMappings')
            ->will($this->returnValue(
                array(
                    array(
                        'url' => 'http://localhost/fake',
                        'method' => 'GET',
                        'response' => array(
                            'status' => 200,
                            'resource' => 'fixtures/fake_get.json'
                            )
                        ),
                    array(
                        'url' => 'http://localhost/fake',
                        'method' => 'POST',
                        'response' => array(
                            'status' => 201,
                            'resource' => 'fixtures/fake_post.json'
                            )
                        ),
                    array(
                        'url' => 'http://localhost/fake',
                        'method' => 'POST',
                        'params' => array(
                                'param1' => 'value1',
                                'param2' => 'value2'
                            ),
                        'response' => array(
                            'status' => 201,
                            'resource' => 'fixtures/fake_post_with_param.json'
                            )
                        ),
                    array(
                        'url' => 'http://localhost/fake',
                        'method' => 'POST',
                        'params' => array(
                                'recursiveParam' => array(
                                    'param1' => 'value1',
                                    'param2' => 'value2'
                                )
                            ),
                        'response' => array(
                            'status' => 201,
                            'resource' => 'fixtures/fake_post_with_recursive_param.json'
                            )
                        )
                )));

        $this->resourceLoaderInterface
            ->expects($this->any())
            ->method('loadResource')
            ->will($this->returnCallback($loadResource));

        $this->fakeServer = new FakeServer($this->configurationInterface, $this->resourceLoaderInterface);
    }

    public function testInitialyNoRequestHaveBeenSended()
    {
        $this->assertCount(0, $this->fakeServer->getReceivedRequests());
    }

    public function testRequestIsFaked()
    {
        $client = new Client();
        
        $client->addSubscriber($this->fakeServer);
        $request = $client->get('http://localhost/fake');
        $response = $request->send();

        $this->assertCount(1, $this->fakeServer->getReceivedRequests());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('get fake response', $response->getBody()->read(100));
        $this->assertEquals($request, $this->fakeServer->getReceivedRequests()[0]);
    }

    public function testVariosRequestsToTheSameResourceOnlyEnquesOneReceivedRequest()
    {
        $client = new Client();
        
        $client->addSubscriber($this->fakeServer);
        $request = $client->get('http://localhost/fake');
        $request->send();
        $request->send();

        $this->assertCount(1, $this->fakeServer->getReceivedRequests());
    }

    public function testRequestIsFakedBasedOnMethod()
    {
        $client = new Client();
        
        $client->addSubscriber($this->fakeServer);
        $request1 = $client->get('http://localhost/fake');
        $response1 = $request1->send();

        $request2 = $client->post('http://localhost/fake');
        $response2 = $request2->send();

        $this->assertCount(2, $this->fakeServer->getReceivedRequests());
        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertEquals('get fake response', $response1->getBody()->read(100));
        $this->assertEquals(201, $response2->getStatusCode());
        $this->assertEquals('post fake response', $response2->getBody()->read(100));
        $this->assertEquals($request2, $this->fakeServer->getReceivedRequests()[1]);
    }

    public function testRequestIsFakedBasedOnMethodAndParams()
    {
        $client = new Client();

        $client->addSubscriber($this->fakeServer);
        
        $request2 = $client->post('http://localhost/fake', null, array(
                'param1' => 'value1',
                'param2' => 'value2'
            ));
        $response2 = $request2->send();

        $this->assertEquals('post fake response with params', $response2->getBody()->read(100));
    }

    public function testRequestIsFakedBasedOnMethodAndRecursiveParamsAndExtraFieldsSended()
    {
        $client = new Client();

        $client->addSubscriber($this->fakeServer);
        
        $request2 = $client->post('http://localhost/fake', null, array(
                'recursiveParam' => array(
                    'param1' => 'value1',
                    'param2' => 'value2',
                    'param3' => 'value3'
                )
            ));
        $response2 = $request2->send();

        $this->assertEquals('post fake response with recursive params', $response2->getBody()->read(100));
    }

    public function testClearReceivedRequests()
    {
        $client = new Client();
        
        $client->addSubscriber($this->fakeServer);
        $request1 = $client->get('http://localhost/fake');
        $response1 = $request1->send();

        $request2 = $client->post('http://localhost/fake');
        $response2 = $request2->send();

        $this->fakeServer->clearReceivedRequests();
        $request1->send();

        $this->assertCount(1, $this->fakeServer->getReceivedRequests());
        $this->assertEquals($request1, $this->fakeServer->getReceivedRequests()[0]);
    }
}