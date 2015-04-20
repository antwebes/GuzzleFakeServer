<?php

namespace Ant\Bundle\GuzzleFakeServer\Guzzle\Plugin\FakeServer;

/**
 * Class that loads a resource, the body content of a response
 */
class FileResourceLoader implements ResourceLoaderInterface
{
    private $basePath;
    private $loadedResources = array();

    /**
     * Constructor
     *
     * @param $basePath Base directory where to search for the resource
     *
     */
    public function __construct($basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * Loads a resource
     *
     * @param $resource path of the resource to load
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