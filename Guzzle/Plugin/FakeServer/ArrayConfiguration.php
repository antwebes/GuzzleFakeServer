<?php

namespace Ant\Bundle\GuzzleFakeServer\Guzzle\Plugin\FakeServer;
 
/**
 * Class that reeds the configuration from a Array
 */         
class ArrayConfiguration implements ConfigurationInterface
{
    private $mappings = array();

    /**
     * Constructor
     *
     * @param $host the host prefix to put in all routes
     */
    public function __construct($host)
    {
        $this->host = $host;
    }

    /**
     * returns an array with the url mappings in the form like:
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
    public function getResourceMappings()
    {
        return $this->mappings;
    }

    /**
     * Clears all mappings
     */
    public function clearMappings()
    {
        $this->mappings = array();
    }

    /**
     * Adds a GET resource
     * @param $url
     * @param $resource
     * @param int $status
     * @param array $params
     */
    public function addGetResource($url, $resource, $status = 200, $params = array())
    {
        $this->addResource('GET', $url, $resource, $params, $status);
    }

    /**
     * Adds a POST resource
     * @param $url
     * @param $resource
     * @param int $status
     * @param array $params
     */
    public function addPostResource($url, $resource, $status = 200, $params = array())
    {
        $this->addResource('POST', $url, $resource, $params, $status);
    }

    /**
     * Adds a PUT resource
     * @param $url
     * @param $resource
     * @param int $status
     * @param array $params
     */
    public function addPutResource($url, $resource, $status = 200, $params = array())
    {
        $this->addResource('PUT', $url, $resource, $params, $status);
    }

    /**
     * Adds a PATCH resource
     * @param $url
     * @param $resource
     * @param int $status
     * @param array $params
     */
    public function addPatchResource($url, $resource, $status = 200, $params = array())
    {
        $this->addResource('PATCH', $url, $resource, $params, $status);
    }

    /**
     * Adds a DELETE resource
     * @param $url
     * @param $resource
     * @param int $status
     * @param array $params
     */
    public function addDeleteResource($url, $resource, $status = 200, $params = array())
    {
        $this->addResource('DELETE', $url, $resource, $params, $status);
    }

    private function addResource($method, $url, $resource, $params = array(), $status = 200)
    {
        $data = array(
            'url' => $this->host.$url,
            'method' => $method,
            'response' => array(
                'status' => $status,
                'resource' => $resource
                )
            );

        if(count($params) > 0){
            $data['params'] = $params;
        }

        $this->mappings[] = $data;
    }
}