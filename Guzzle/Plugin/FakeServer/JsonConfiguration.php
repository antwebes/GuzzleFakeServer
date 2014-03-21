<?php

namespace Ant\Bundle\GuzzleFakeServer\Guzzle\Plugin\FakeServer;
 
/**
 * Clase que lee la configuración de un archivo JSON
 */         
class JsonConfiguration implements ConfigurationInterface
{
    private $host;
    private $configFile;
    private $mappings = null;

    /**
     * Constructor
     *
     * @param $host el prefijo que se le va a poner a todas las rutas especificadas en el archivo de configuración
     * @param configFile ruta al archivo de configuración JSON
     */
    public function __construct($host, $configFile)
    {
        $this->host = $host;
        $this->configFile = $configFile;
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
        if($this->mappings == null){
            $this->loadMappings();
        }

        return $this->mappings;
    }

    private function loadMappings()
    {
        $mappings = json_decode(file_get_contents($this->configFile), true);

        //una vez que tengo todos los mappings en el parámetro url le pongo el host por delante
        foreach($mappings as $key => $mapping){
            $mapping['url'] = $this->host.$mapping['url'];
            $mappings[$key] = $mapping;
        }

        $this->mappings = $mappings;
    }
}