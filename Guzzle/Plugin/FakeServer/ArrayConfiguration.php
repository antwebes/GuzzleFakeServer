<?php

namespace Ant\Bundle\GuzzleFakeServer\Guzzle\Plugin\FakeServer;
 
/**
 * Clase que lee la configuración de un archivo JSON
 */         
class ArrayConfiguration implements ConfigurationInterface
{
    private $mappings = array();

    /**
     * Constructor
     *
     * @param $host el prefijo que se le va a poner a todas las rutas especificadas en el archivo de configuración
     * @param configFile ruta al archivo de configuración JSON
     */
    public function __construct($host)
    {
        $this->host = $host;
    }

    /**
     * retorna un array con el mappeo de urls compo por ejemplo:
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

    public function clearMappings()
    {
        $this->mappings = array();
    }

    public function addGetResource($url, $resource, $status = 200, $params = array())
    {
        $this->addResource('GET', $url, $resource, $params, $status);
    }

    public function addPostResource($url, $resource, $status = 200, $params = array())
    {
        $this->addResource('POST', $url, $resource, $params, $status);
    }

    public function addPutResource($url, $resource, $status = 200, $params = array())
    {
        $this->addResource('PUT', $url, $resource, $params, $status);
    }

    public function addPatchResource($url, $resource, $status = 200, $params = array())
    {
        $this->addResource('PATCH', $url, $resource, $params, $status);
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