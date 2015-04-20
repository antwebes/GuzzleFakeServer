# Guzzle Fake Server


Guzzle Fake Server is a pluggin for Guzzle client to simulate server responses.

Instalation
=================

Add to the composer.json file the ```"antwebes/guzzle-fake-server": "dev-master"``` line an then execute: ```$ composer update```.

Design
=================


![Class diagram](https://raw.githubusercontent.com/antwebes/GuzzleFakeServer/development/doc/img/guzzle_fake_server_diagram.png)
![Sequence diagram](https://raw.githubusercontent.com/antwebes/GuzzleFakeServer/development/doc/img/guzzle_fake_server_sequence.png)

As you can see in the diagram, when you make a call to guzzle, the FakeServer listens to an event, searches for a resource and loads it from disk to simulate the response.

Usage
=================
To use the FakeServe you basicly create an ConfirgurationInterface instance where you define the mappings between the URLs and the resources to map and an ResoureceLoaderInterface instance responsible to load a resource.

```
<?php

use Ant\Bundle\GuzzleFakeServer\Guzzle\Plugin\FakeServer\FakeServer;
use Ant\Bundle\GuzzleFakeServer\Guzzle\Plugin\FakeServer\FileResourceLoader;
use Ant\Bundle\GuzzleFakeServer\Guzzle\Plugin\FakeServer\ArrayConfiguration;
use Guzzle\Client;

$client = Clinet;
$resourcesBasePath = __DIR__ . '/path/to/fixtures/fixtures/';
$fakeServerMappings = new ArrayConfiguration("http://asuperhost.com/");
$loader = new FileResourceLoader($resourcesBasePath);
$fakeServer = new FakeServer(
    $fakeServerMappings, 
    $loader, 
    200, 
    '""', 
    array('Content-Type' => 'application/json'));

$fakeServerMappings->addPostResource(
            '/oauth/v2/token', //the url beeing called
            'fixtures/login/success_client_login.json', //the resource to be loaded
            200, //the status code that will be retourened
            array( //the data expected to receive in the request
                "grant_type" => "client_credentials",
                "client_id" => "1_social_client_id",
                "client_secret" => "social_client_secret"
            ));

$client->addSubscriber($fakeServer);
$client->post("http://asuperhost.com/oauth/v2/token",
              array( //the data expected to receive in the request
                "grant_type" => "client_credentials",
                "client_id" => "1_social_client_id",
                "client_secret" => "social_client_secret"
            )); //the response will be teaken from /path/to/fixtures/fixtures/fixtures/login/success_client_login.json
```

Configuration
=================

The configuration object is the responsible for configuring the mapping between the URL and the resource to return.

ArrayConfiguration
------------------
Sotres the mappings in a array. The constructor takes a base host as its first argument (```$fakeServerMappings = new ArrayConfiguration("http://asuperhost.com/");```).

To add mappings you can add GET, POST, PUT, PATCH or DELETE resources like:

```
$fakeServerMappings->addGetResource(
            '/users/top', //the url beeing called
            'fixtures/users_top.json' //the file contains the response content,
            200):
```

The aviaible methods are:

* ```addGetResource($url, $resource, $status = 200, $params = array())```
* ```addPostResource($url, $resource, $status = 200, $params = array())```
* ```addPutResource($url, $resource, $status = 200, $params = array())```
* ```addPatchResource($url, $resource, $status = 200, $params = array())```
* ```addResource($method, $url, $resource, $status = 200, $params = array())```
 
Example of conditional resource mapping to a same url (post for example)

```
$fakeServerMappings->addPostResource(
            '/oauth/v2/token', //the url beeing called
            'fixtures/login/success_client_login1.json', //the resource to be loaded
            200, //the status code that will be retourened
            array( //the data expected to receive in the request
                "grant_type" => "client_credentials",
                "client_id" => "1_social_client_id",
                "client_secret" => "social_client_secret"
            ));
$fakeServerMappings->addPostResource(
            '/oauth/v2/token', //the url beeing called
            'fixtures/login/success_client_login2.json', //the resource to be loaded
            200, //the status code that will be retourened
            array( //the data expected to receive in the request
                "grant_type" => "client_credentials2",
                "client_id" => "2_social_client_id",
                "client_secret" => "social_client_secret"
            ));
            
$client->addSubscriber($fakeServer);

$client->post("http://asuperhost.com/oauth/v2/token",
              array( //the data expected to receive in the request
                "grant_type" => "client_credentials",
                "client_id" => "1_social_client_id",
                "client_secret" => "social_client_secret"
            )); //the response will be teaken from /path/to/fixtures/fixtures/fixtures/login/success_client_login1.json
$client->post("http://asuperhost.com/oauth/v2/token",
              array( //the data expected to receive in the request
                "grant_type" => "client_credentials2",
                "client_id" => "2_social_client_id",
                "client_secret" => "social_client_secret"
            )); //the response will be teaken from /path/to/fixtures/fixtures/fixtures/login/success_client_login2.json
```