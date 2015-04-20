<?php

namespace Ant\Bundle\GuzzleFakeServer\Guzzle\Plugin\FakeServer;

/**
 * Interfaz para cargar recursos, es decir, el contenido del body de la respuesta
 */
interface ResourceLoaderInterface
{
    /**
     * Loads a resource
     *
     * @param $resource path of the resource to load
     *
     * @return string
     */
    public function loadResource($resource);
}