<?php

namespace Ant\Bundle\GuzzleFakeServer\Guzzle\Plugin\FakeServer;

/**
 * Clase para cargar recursos, es decir, el contenido del body de la respuesta
 */
class FileResourceLoader implements ResourceLoaderInterface
{
    private $basePath;
    private $loadedResources = array();

    /**
     * Constructor
     *
     * @param $basePath Directorio base en el cual buscar 
     *
     */
    public function __construct($basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * Carga un recurso
     *
     * @param $resource ruta al recurso a cargar
     *
     * @return string
     */
    public function loadResource($resource)
    {
        $path = dirname($this->basePath)."/".$resource;

        if(!isset($this->loadedResources[$path])){
            $this->loadFile($path);
        }

        return $this->loadedResources[$path];
    }

    private function loadFile($path)
    {
        $this->loadedResources[$path] = file_get_contents($path);
    }
}