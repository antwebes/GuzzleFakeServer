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
     * @param $host the host prefix to put in all routes
     * @param configFile path to the JSON configuration file
     */
    public function __construct($host, $configFile)
    {
        $this->host = $host;
        $this->configFile = $configFile;
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
        if($this->mappings == null){
            $this->loadMappings();
        }

        return $this->mappings;
    }

    private function loadMappings()
    {
        $mappings = json_decode(file_get_contents($this->configFile), true);

        //once I have all mappings loaded I prefix to all URLs the host
        foreach($mappings as $key => $mapping){
            $mapping['url'] = $this->host.$mapping['url'];
            $mappings[$key] = $mapping;
        }

        $this->mappings = $mappings;
    }
}