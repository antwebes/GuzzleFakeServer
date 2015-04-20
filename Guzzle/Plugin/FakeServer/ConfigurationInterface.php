<?php

namespace Ant\Bundle\GuzzleFakeServer\Guzzle\Plugin\FakeServer;

/**
 * Interfaz para cargar los mappings de urls en el fake server
 */          
interface ConfigurationInterface
{
    /**
     *
     * returns an array with the url mappings in the forme like:
     * 
     * array(
     *      'url' => 'http://localhost/fake',
     *      'method' => 'POST',
     *      'params' => array(
     *              'param1' => 'value1',
     *              'param2' => 'value2'
     *          ),
     *      'response' => array(
     *          'status' => 201,
     *          'resource' => 'fixtures/fake_post_with_param.json'
     *          )
     *      )
     * @return array
     */
    public function getResourceMappings();
}